<?php

declare(strict_types=1);

namespace App\User\Models;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
