<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AccesoICloud;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccesoICloudPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccesoICloud');
    }

    public function view(AuthUser $authUser, AccesoICloud $accesoICloud): bool
    {
        return $authUser->can('View:AccesoICloud');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccesoICloud');
    }

    public function update(AuthUser $authUser, AccesoICloud $accesoICloud): bool
    {
        return $authUser->can('Update:AccesoICloud');
    }

    public function delete(AuthUser $authUser, AccesoICloud $accesoICloud): bool
    {
        return $authUser->can('Delete:AccesoICloud');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AccesoICloud');
    }

    public function restore(AuthUser $authUser, AccesoICloud $accesoICloud): bool
    {
        return $authUser->can('Restore:AccesoICloud');
    }

    public function forceDelete(AuthUser $authUser, AccesoICloud $accesoICloud): bool
    {
        return $authUser->can('ForceDelete:AccesoICloud');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccesoICloud');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccesoICloud');
    }

    public function replicate(AuthUser $authUser, AccesoICloud $accesoICloud): bool
    {
        return $authUser->can('Replicate:AccesoICloud');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccesoICloud');
    }

}