<?php

declare(strict_types=1);

namespace App\Models;

use App\Messaging\SenderRole;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property SenderRole $sender_role
 * @property ?int $user_id
 * @property ?string $character_id
 * @property string $content
 * @property ?string $model
 * @property ?int $tokens_usage
 */
#[Fillable([
    'chat_id', 'sender_role', 'user_id', 'character_id',
    'content', 'model', 'tokens_usage', 'read_at',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return BelongsTo<Chat, $this>
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function sender(): User|Character
    {
        return match ($this->sender_role) {
            SenderRole::User => $this->user,
            SenderRole::Character => $this->character,
        };
    }

    public function bubbleSide(): string
    {
        return $this->sender_role->bubbleSide();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_role' => SenderRole::class,
            'tokens_usage' => 'integer',
            'read_at' => 'datetime',
        ];
    }
}
