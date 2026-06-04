<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EquipoCelular;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipoCelularPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EquipoCelular');
    }

    public function view(AuthUser $authUser, EquipoCelular $equipoCelular): bool
    {
        return $authUser->can('View:EquipoCelular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipoCelular');
    }

    public function update(AuthUser $authUser, EquipoCelular $equipoCelular): bool
    {
        return $authUser->can('Update:EquipoCelular');
    }

    public function delete(AuthUser $authUser, EquipoCelular $equipoCelular): bool
    {
        return $authUser->can('Delete:EquipoCelular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EquipoCelular');
    }

    public function restore(AuthUser $authUser, EquipoCelular $equipoCelular): bool
    {
        return $authUser->can('Restore:EquipoCelular');
    }

    public function forceDelete(AuthUser $authUser, EquipoCelular $equipoCelular): bool
    {
        return $authUser->can('ForceDelete:EquipoCelular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipoCelular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipoCelular');
    }

    public function replicate(AuthUser $authUser, EquipoCelular $equipoCelular): bool
    {
        return $authUser->can('Replicate:EquipoCelular');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipoCelular');
    }

}