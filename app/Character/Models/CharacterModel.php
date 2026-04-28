<?php

declare(strict_types=1);

namespace App\Character\Models;

use App\Character\Enums\CharacterKind;
use App\Chat\Models\ChatModel;
use App\Dating\Models\DatingProfileModel;
use App\User\Models\UserModel;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Plank\Mediable\Mediable;
use Plank\Mediable\MediableInterface;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;

/**
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string|null $slug
 * @property string|null $description
 * @property string $prompt
 * @property string|null $greeting
 * @property CharacterKind $kind
 * @property bool $is_official
 * @property int $popularity_24h
 */
#[Fillable(['user_id', 'name', 'slug', 'description', 'prompt', 'greeting', 'kind', 'is_official'])]
class CharacterModel extends Model implements MediableInterface
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory, HasTags, HasUlids, Mediable, SoftDeletes;

    protected $table = 'characters';

    /** @var array<string, mixed> */
    protected $attributes = [
        'kind' => 'regular',
        'is_official' => false,
        'popularity_24h' => 0,
    ];

    public string $initials {
        get {
            $name = (string) $this->getAttribute('name');
            $words = preg_split('/\s+/u', trim($name)) ?: [];
            $first = isset($words[0]) ? mb_substr($words[0], 0, 1) : '';
            $second = isset($words[1]) ? mb_substr($words[1], 0, 1) : '';

            return mb_strtoupper($first.$second) ?: '?';
        }
    }

    /**
     * Tailwind: bg-gradient-to-br from-violet to-magenta from-crimson from-cyan from-orange from-rose to-violet to-orange to-crimson
     * (powyższy komentarz pomaga Tailwind scanner zobaczyć literały klas — patrz @source w app.css)
     */
    public string $avatar_gradient_class {
        get {
            $palette = [
                'bg-gradient-to-br from-violet to-magenta',
                'bg-gradient-to-br from-crimson to-magenta',
                'bg-gradient-to-br from-cyan to-violet',
                'bg-gradient-to-br from-orange to-crimson',
                'bg-gradient-to-br from-rose to-magenta',
                'bg-gradient-to-br from-cyan to-orange',
            ];

            return $palette[crc32((string) $this->getAttribute('name')) % count($palette)];
        }
    }

    public ?string $role_label {
        get {
            $tag = $this->relationLoaded('tags')
                ? ($this->tags->firstWhere('type', 'tag') ?? $this->tags->firstWhere('type', 'category'))
                : ($this->freeTags()->first() ?? $this->categories()->first());

            /** @phpstan-ignore property.notFound */
            return $tag?->name;
        }
    }

    public function getForeignKey(): string
    {
        return 'character_id';
    }

    protected static function booted(): void
    {
        static::saving(function (CharacterModel $character): void {
            if ($character->kind === CharacterKind::Regular && ($character->slug === null || $character->slug === '')) {
                $character->slug = self::generateUniqueSlug($character->name, $character->id);
            }
        });

        static::deleting(function (CharacterModel $character): void {
            if ($character->isForceDeleting()) {
                return;
            }

            $character->chats()->each(fn (ChatModel $chat) => $chat->delete());
        });

        static::restoring(function (CharacterModel $character): void {
            $character->chats()->onlyTrashed()->restore();
        });
    }

    private static function generateUniqueSlug(string $name, ?string $ignoreId): string
    {
        $base = Str::slug($name) ?: 'postac';
        $candidate = $base;
        $counter = 2;

        while (self::query()
            ->where('slug', $candidate)
            ->when($ignoreId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->withTrashed()
            ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    public function avatarUrl(string $variant = 'square'): string
    {
        $media = $this->getMedia('avatar')->first();

        if ($media === null) {
            return "https://api.dicebear.com/9.x/bottts/svg?seed={$this->id}";
        }

        return $media->hasVariant($variant) ? $media->findVariant($variant)->getUrl() : $media->getUrl();
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * @return HasMany<ChatModel, $this>
     */
    public function chats(): HasMany
    {
        return $this->hasMany(ChatModel::class, 'character_id');
    }

    /**
     * @return HasOne<DatingProfileModel, $this>
     */
    public function datingProfile(): HasOne
    {
        return $this->hasOne(DatingProfileModel::class, 'character_id');
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function categories(): MorphToMany
    {
        /** @var MorphToMany<Tag, $this> $relation */
        $relation = $this->tags()->where('type', 'category');

        return $relation;
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function freeTags(): MorphToMany
    {
        /** @var MorphToMany<Tag, $this> $relation */
        $relation = $this->tags()->where('type', 'tag');

        return $relation;
    }

    /**
     * @param  Builder<CharacterModel>  $query
     */
    public function scopeOfficial(Builder $query): void
    {
        $query->where('is_official', true);
    }

    /**
     * @param  Builder<CharacterModel>  $query
     */
    public function scopeRegular(Builder $query): void
    {
        $query->where('kind', CharacterKind::Regular);
    }

    /**
     * @param  Builder<CharacterModel>  $query
     */
    public function scopeDating(Builder $query): void
    {
        $query->where('kind', CharacterKind::Dating);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CharacterKind::class,
            'is_official' => 'boolean',
            'popularity_24h' => 'integer',
        ];
    }
}
