<?php

declare(strict_types=1);

namespace App\User\Models;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\Legal\Models\ConsentModel;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $password
 * @property Carbon|null $birthdate
 * @property Carbon|null $email_verified_at
 */
#[Fillable(['name', 'email', 'password', 'birthdate'])]
#[Hidden(['password', 'remember_token'])]
class UserModel extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    /**
     * Override Eloquent's snake_case(class_basename) → 'user_model_id' guess.
     * All FKs in DB are 'user_id'.
     */
    public function getForeignKey(): string
    {
        return 'user_id';
    }

    /**
     * @return HasMany<CharacterModel, $this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(CharacterModel::class, 'user_id');
    }

    /**
     * @return HasMany<ChatModel, $this>
     */
    public function chats(): HasMany
    {
        return $this->hasMany(ChatModel::class, 'user_id');
    }

    /**
     * @return HasMany<MessageLimitModel, $this>
     */
    public function messageLimits(): HasMany
    {
        return $this->hasMany(MessageLimitModel::class, 'user_id');
    }

    /**
     * @return HasMany<ConsentModel, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(ConsentModel::class, 'user_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isGuest(): bool
    {
        return $this->email === null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeGuests(Builder $query): void
    {
        $query->whereNull('email');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeRegistered(Builder $query): void
    {
        $query->whereNotNull('email');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
        ];
    }
}
