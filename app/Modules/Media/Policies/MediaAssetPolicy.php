<?php

namespace App\Modules\Media\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEditContent();
    }

    public function view(User $user, Model $media): bool
    {
        return $user->canEditContent();
    }

    public function create(User $user): bool
    {
        return $user->canEditContent();
    }

    public function update(User $user, Model $media): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, Model $media): bool
    {
        return $user->canEditContent() && $media->canBeDeleted();
    }
}
