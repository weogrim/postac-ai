<?php

declare(strict_types=1);

namespace App\Character\Models;

use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Mediable\Mediable;
use Plank\Mediable\MediableInterface;

#[Fillable(['user_id', 'name', 'prompt'])]
class CharacterModel extends Model implements MediableInterface
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory, HasUlids, Mediable, SoftDeletes;

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
}
