<?php

declare(strict_types=1);

namespace App\Character\Policies;

use App\Character\Models\CharacterModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CharacterPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Character');
    }

    public function view(AuthUser $authUser, CharacterModel $character): bool
    {
        return $authUser->can('View:Character');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Character');
    }

    public function update(AuthUser $authUser, CharacterModel $character): bool
    {
        return $authUser->can('Update:Character');
    }

    public function delete(AuthUser $authUser, CharacterModel $character): bool
    {
        return $authUser->can('Delete:Character');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Character');
    }

    public function restore(AuthUser $authUser, CharacterModel $character): bool
    {
        return $authUser->can('Restore:Character');
    }

    public function forceDelete(AuthUser $authUser, CharacterModel $character): bool
    {
        return $authUser->can('ForceDelete:Character');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Character');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Character');
    }

    public function replicate(AuthUser $authUser, CharacterModel $character): bool
    {
        return $authUser->can('Replicate:Character');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Character');
    }
}
