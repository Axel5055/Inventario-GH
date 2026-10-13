<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TipoDispositivo;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoDispositivoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TipoDispositivo');
    }

    public function view(AuthUser $authUser, TipoDispositivo $tipoDispositivo): bool
    {
        return $authUser->can('View:TipoDispositivo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TipoDispositivo');
    }

    public function update(AuthUser $authUser, TipoDispositivo $tipoDispositivo): bool
    {
        return $authUser->can('Update:TipoDispositivo');
    }

    public function delete(AuthUser $authUser, TipoDispositivo $tipoDispositivo): bool
    {
        return $authUser->can('Delete:TipoDispositivo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TipoDispositivo');
    }

    public function restore(AuthUser $authUser, TipoDispositivo $tipoDispositivo): bool
    {
        return $authUser->can('Restore:TipoDispositivo');
    }

    public function forceDelete(AuthUser $authUser, TipoDispositivo $tipoDispositivo): bool
    {
        return $authUser->can('ForceDelete:TipoDispositivo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TipoDispositivo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TipoDispositivo');
    }

    public function replicate(AuthUser $authUser, TipoDispositivo $tipoDispositivo): bool
    {
        return $authUser->can('Replicate:TipoDispositivo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TipoDispositivo');
    }

}