<?php

declare(strict_types=1);

namespace App\Chat\Models;

use App\Character\Models\CharacterModel;
use App\User\Models\UserModel;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'character_id'])]
class ChatModel extends Model
{
    /** @use HasFactory<ChatFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'chats';

    public function getForeignKey(): string
    {
        return 'chat_id';
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * @return BelongsTo<CharacterModel, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(CharacterModel::class, 'character_id');
    }

    /**
     * @return HasMany<MessageModel, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MessageModel::class, 'chat_id');
    }
}
