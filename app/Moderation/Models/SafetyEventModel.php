<?php

declare(strict_types=1);

namespace App\Moderation\Models;

use App\User\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $category
 * @property Carbon $created_at
 */
class SafetyEventModel extends Model
{
    protected $table = 'safety_events';

    public $timestamps = false;

    protected $fillable = ['user_id', 'category', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class);
    }
}
