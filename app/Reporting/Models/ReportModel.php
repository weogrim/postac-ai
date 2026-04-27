<?php

declare(strict_types=1);

namespace App\Reporting\Models;

use App\Reporting\Enums\ReportReason;
use App\Reporting\Enums\ReportStatus;
use App\User\Models\UserModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reporter_id
 * @property string $reportable_type
 * @property string $reportable_id
 * @property ReportReason $reason
 * @property string|null $description
 * @property ReportStatus $status
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ReportModel extends Model
{
    protected $table = 'reports';

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'reason' => ReportReason::class,
        'status' => ReportStatus::class,
        'resolved_at' => 'datetime',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'resolved_by');
    }

    /**
     * @param  Builder<ReportModel>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ReportStatus::Pending->value);
    }

    /**
     * @param  Builder<ReportModel>  $query
     */
    public function scopeOverdue(Builder $query, int $hours = 24): void
    {
        $query->where('status', ReportStatus::Pending->value)
            ->where('created_at', '<', now()->subHours($hours));
    }
}
