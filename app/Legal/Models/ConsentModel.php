<?php

declare(strict_types=1);

namespace App\Legal\Models;

use App\User\Models\UserModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $legal_document_id
 * @property Carbon $accepted_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['user_id', 'legal_document_id', 'accepted_at', 'ip_address', 'user_agent'])]
class ConsentModel extends Model
{
    protected $table = 'consents';

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * @return BelongsTo<LegalDocumentModel, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentModel::class, 'legal_document_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }
}
