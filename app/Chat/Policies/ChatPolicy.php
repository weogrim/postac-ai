<?php

declare(strict_types=1);

namespace App\Chat\Policies;

use App\Chat\Models\ChatModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChatPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Chat');
    }

    public function view(AuthUser $authUser, ChatModel $chat): bool
    {
        return $authUser->can('View:Chat');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Chat');
    }

    public function update(AuthUser $authUser, ChatModel $chat): bool
    {
        return $authUser->can('Update:Chat');
    }

    public function delete(AuthUser $authUser, ChatModel $chat): bool
    {
        return $authUser->can('Delete:Chat');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Chat');
    }

    public function restore(AuthUser $authUser, ChatModel $chat): bool
    {
        return $authUser->can('Restore:Chat');
    }

    public function forceDelete(AuthUser $authUser, ChatModel $chat): bool
    {
        return $authUser->can('ForceDelete:Chat');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Chat');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Chat');
    }

    public function replicate(AuthUser $authUser, ChatModel $chat): bool
    {
        return $authUser->can('Replicate:Chat');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Chat');
    }
}
