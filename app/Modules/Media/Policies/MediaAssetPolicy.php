<?php

namespace App\Modules\Media\Policies;

use App\Models\User;
use App\Modules\Media\Models\MediaAsset;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEditContent();
    }

    public function view(User $user, MediaAsset $media): bool
    {
        return $user->canEditContent();
    }

    public function create(User $user): bool
    {
        return $user->canEditContent();
    }

    public function update(User $user, MediaAsset $media): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, MediaAsset $media): bool
    {
        return $user->canEditContent() && $media->canBeDeleted();
    }
}
