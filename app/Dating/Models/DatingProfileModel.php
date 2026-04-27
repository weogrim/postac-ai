<?php

declare(strict_types=1);

namespace App\Dating\Models;

use App\Character\Models\CharacterModel;
use Database\Factories\DatingProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $character_id
 * @property int $age
 * @property string $city
 * @property string $bio
 * @property array<int, string> $interests
 * @property string|null $accent_color
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DatingProfileModel extends Model
{
    /** @use HasFactory<DatingProfileFactory> */
    use HasFactory;

    protected $table = 'dating_profiles';

    protected $primaryKey = 'character_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'character_id',
        'age',
        'city',
        'bio',
        'interests',
        'accent_color',
    ];

    protected $casts = [
        'age' => 'integer',
        'interests' => 'array',
    ];

    /**
     * @return BelongsTo<CharacterModel, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(CharacterModel::class, 'character_id');
    }
}
