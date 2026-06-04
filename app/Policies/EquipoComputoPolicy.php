<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EquipoComputo;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipoComputoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EquipoComputo');
    }

    public function view(AuthUser $authUser, EquipoComputo $equipoComputo): bool
    {
        return $authUser->can('View:EquipoComputo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipoComputo');
    }

    public function update(AuthUser $authUser, EquipoComputo $equipoComputo): bool
    {
        return $authUser->can('Update:EquipoComputo');
    }

    public function delete(AuthUser $authUser, EquipoComputo $equipoComputo): bool
    {
        return $authUser->can('Delete:EquipoComputo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EquipoComputo');
    }

    public function restore(AuthUser $authUser, EquipoComputo $equipoComputo): bool
    {
        return $authUser->can('Restore:EquipoComputo');
    }

    public function forceDelete(AuthUser $authUser, EquipoComputo $equipoComputo): bool
    {
        return $authUser->can('ForceDelete:EquipoComputo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipoComputo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipoComputo');
    }

    public function replicate(AuthUser $authUser, EquipoComputo $equipoComputo): bool
    {
        return $authUser->can('Replicate:EquipoComputo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipoComputo');
    }

}