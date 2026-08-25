<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RazonSocial;
use Illuminate\Auth\Access\HandlesAuthorization;

class RazonSocialPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RazonSocial');
    }

    public function view(AuthUser $authUser, RazonSocial $razonSocial): bool
    {
        return $authUser->can('View:RazonSocial');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RazonSocial');
    }

    public function update(AuthUser $authUser, RazonSocial $razonSocial): bool
    {
        return $authUser->can('Update:RazonSocial');
    }

    public function delete(AuthUser $authUser, RazonSocial $razonSocial): bool
    {
        return $authUser->can('Delete:RazonSocial');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RazonSocial');
    }

    public function restore(AuthUser $authUser, RazonSocial $razonSocial): bool
    {
        return $authUser->can('Restore:RazonSocial');
    }

    public function forceDelete(AuthUser $authUser, RazonSocial $razonSocial): bool
    {
        return $authUser->can('ForceDelete:RazonSocial');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RazonSocial');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RazonSocial');
    }

    public function replicate(AuthUser $authUser, RazonSocial $razonSocial): bool
    {
        return $authUser->can('Replicate:RazonSocial');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RazonSocial');
    }

}