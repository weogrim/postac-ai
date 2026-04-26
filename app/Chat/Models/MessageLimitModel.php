<?php

declare(strict_types=1);

namespace App\Chat\Models;

use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\User\Models\UserModel;
use Database\Factories\MessageLimitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property ModelType $model_type
 * @property LimitType $limit_type
 * @property int $priority
 * @property int $quota
 * @property int $used
 * @property Carbon|null $period_start
 */
#[Fillable([
    'user_id', 'model_type', 'limit_type', 'priority',
    'quota', 'used', 'period_start',
])]
class MessageLimitModel extends Model
{
    /** @use HasFactory<MessageLimitFactory> */
    use HasFactory;

    protected $table = 'message_limits';

    public function getForeignKey(): string
    {
        return 'message_limit_id';
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, UserModel $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereColumn('used', '<', 'quota');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCurrentWindow(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('limit_type', LimitType::Package->value)
                ->orWhere(function (Builder $daily): void {
                    $daily->where('limit_type', LimitType::Daily->value)
                        ->where('period_start', '>=', now()->subDay());
                });
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'model_type' => ModelType::class,
            'limit_type' => LimitType::class,
            'priority' => 'integer',
            'quota' => 'integer',
            'used' => 'integer',
            'period_start' => 'datetime',
        ];
    }
}
