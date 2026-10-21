<?php

namespace App\Modules\Media\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEditContent() && $user->canAccessContent('media');
    }

    public function view(User $user, Model $media): bool
    {
        return $user->canEditContent() && $user->canAccessContent('media');
    }

    public function create(User $user): bool
    {
        return $user->canEditContent() && $user->canAccessContent('media');
    }

    public function update(User $user, Model $media): bool
    {
        return $user->isAdministrator() || ($user->canEditContent() && $user->canAccessContent('media'));
    }

    public function delete(User $user, Model $media): bool
    {
        return $user->canEditContent() && $user->canAccessContent('media') && $media->canBeDeleted();
    }
}
