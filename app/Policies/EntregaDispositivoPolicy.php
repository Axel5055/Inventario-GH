<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EntregaDispositivo;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntregaDispositivoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EntregaDispositivo');
    }

    public function view(AuthUser $authUser, EntregaDispositivo $entregaDispositivo): bool
    {
        return $authUser->can('View:EntregaDispositivo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EntregaDispositivo');
    }

    public function update(AuthUser $authUser, EntregaDispositivo $entregaDispositivo): bool
    {
        return $authUser->can('Update:EntregaDispositivo');
    }

    public function delete(AuthUser $authUser, EntregaDispositivo $entregaDispositivo): bool
    {
        return $authUser->can('Delete:EntregaDispositivo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EntregaDispositivo');
    }

    public function restore(AuthUser $authUser, EntregaDispositivo $entregaDispositivo): bool
    {
        return $authUser->can('Restore:EntregaDispositivo');
    }

    public function forceDelete(AuthUser $authUser, EntregaDispositivo $entregaDispositivo): bool
    {
        return $authUser->can('ForceDelete:EntregaDispositivo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EntregaDispositivo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EntregaDispositivo');
    }

    public function replicate(AuthUser $authUser, EntregaDispositivo $entregaDispositivo): bool
    {
        return $authUser->can('Replicate:EntregaDispositivo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EntregaDispositivo');
    }

}