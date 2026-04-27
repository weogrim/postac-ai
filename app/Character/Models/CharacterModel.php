<?php

declare(strict_types=1);

namespace App\Character\Models;

use App\Character\Enums\CharacterKind;
use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Mediable\Mediable;
use Plank\Mediable\MediableInterface;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;

/**
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string $prompt
 * @property string|null $greeting
 * @property CharacterKind $kind
 * @property bool $is_official
 * @property int $popularity_24h
 */
#[Fillable(['user_id', 'name', 'description', 'prompt', 'greeting', 'kind', 'is_official'])]
class CharacterModel extends Model implements MediableInterface
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory, HasTags, HasUlids, Mediable, SoftDeletes;

    protected $table = 'characters';

    public function getForeignKey(): string
    {
        return 'character_id';
    }

    protected static function booted(): void
    {
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
