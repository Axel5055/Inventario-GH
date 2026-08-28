<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SuscripcionOffice365;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuscripcionOffice365Policy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SuscripcionOffice365');
    }

    public function view(AuthUser $authUser, SuscripcionOffice365 $suscripcionOffice365): bool
    {
        return $authUser->can('View:SuscripcionOffice365');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SuscripcionOffice365');
    }

    public function update(AuthUser $authUser, SuscripcionOffice365 $suscripcionOffice365): bool
    {
        return $authUser->can('Update:SuscripcionOffice365');
    }

    public function delete(AuthUser $authUser, SuscripcionOffice365 $suscripcionOffice365): bool
    {
        return $authUser->can('Delete:SuscripcionOffice365');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SuscripcionOffice365');
    }

    public function restore(AuthUser $authUser, SuscripcionOffice365 $suscripcionOffice365): bool
    {
        return $authUser->can('Restore:SuscripcionOffice365');
    }

    public function forceDelete(AuthUser $authUser, SuscripcionOffice365 $suscripcionOffice365): bool
    {
        return $authUser->can('ForceDelete:SuscripcionOffice365');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SuscripcionOffice365');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SuscripcionOffice365');
    }

    public function replicate(AuthUser $authUser, SuscripcionOffice365 $suscripcionOffice365): bool
    {
        return $authUser->can('Replicate:SuscripcionOffice365');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SuscripcionOffice365');
    }

}