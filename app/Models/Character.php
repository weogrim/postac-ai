<?php

declare(strict_types=1);

namespace App\Models;

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
class Character extends Model implements MediableInterface
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory, HasUlids, Mediable, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Character $character): void {
            if ($character->isForceDeleting()) {
                return;
            }

            $character->chats()->each(fn (Chat $chat) => $chat->delete());
        });

        static::restoring(function (Character $character): void {
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
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Chat, $this>
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
