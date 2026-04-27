<?php

declare(strict_types=1);

namespace App\Legal\Models;

use App\Legal\Enums\DocumentSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property DocumentSlug $slug
 * @property int $version
 * @property string $title
 * @property string $content
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['slug', 'version', 'title', 'content', 'published_at'])]
class LegalDocumentModel extends Model
{
    protected $table = 'legal_documents';

    public function getForeignKey(): string
    {
        return 'legal_document_id';
    }

    /**
     * @return HasMany<ConsentModel, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(ConsentModel::class, 'legal_document_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slug' => DocumentSlug::class,
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
