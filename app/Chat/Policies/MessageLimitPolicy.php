<?php

declare(strict_types=1);

namespace App\Chat\Policies;

use App\Chat\Models\MessageLimitModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MessageLimitPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MessageLimit');
    }

    public function view(AuthUser $authUser, MessageLimitModel $messageLimit): bool
    {
        return $authUser->can('View:MessageLimit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MessageLimit');
    }

    public function update(AuthUser $authUser, MessageLimitModel $messageLimit): bool
    {
        return $authUser->can('Update:MessageLimit');
    }

    public function delete(AuthUser $authUser, MessageLimitModel $messageLimit): bool
    {
        return $authUser->can('Delete:MessageLimit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MessageLimit');
    }

    public function restore(AuthUser $authUser, MessageLimitModel $messageLimit): bool
    {
        return $authUser->can('Restore:MessageLimit');
    }

    public function forceDelete(AuthUser $authUser, MessageLimitModel $messageLimit): bool
    {
        return $authUser->can('ForceDelete:MessageLimit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MessageLimit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MessageLimit');
    }

    public function replicate(AuthUser $authUser, MessageLimitModel $messageLimit): bool
    {
        return $authUser->can('Replicate:MessageLimit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MessageLimit');
    }
}
