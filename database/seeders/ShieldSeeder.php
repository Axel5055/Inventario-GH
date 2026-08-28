<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:EntregaDispositivo","View:EntregaDispositivo","Create:EntregaDispositivo","Update:EntregaDispositivo","Delete:EntregaDispositivo","DeleteAny:EntregaDispositivo","Restore:EntregaDispositivo","ForceDelete:EntregaDispositivo","ForceDeleteAny:EntregaDispositivo","RestoreAny:EntregaDispositivo","Replicate:EntregaDispositivo","Reorder:EntregaDispositivo","ViewAny:EquipoCelular","View:EquipoCelular","Create:EquipoCelular","Update:EquipoCelular","Delete:EquipoCelular","DeleteAny:EquipoCelular","Restore:EquipoCelular","ForceDelete:EquipoCelular","ForceDeleteAny:EquipoCelular","RestoreAny:EquipoCelular","Replicate:EquipoCelular","Reorder:EquipoCelular","ViewAny:EquipoComputo","View:EquipoComputo","Create:EquipoComputo","Update:EquipoComputo","Delete:EquipoComputo","DeleteAny:EquipoComputo","Restore:EquipoComputo","ForceDelete:EquipoComputo","ForceDeleteAny:EquipoComputo","RestoreAny:EquipoComputo","Replicate:EquipoComputo","Reorder:EquipoComputo","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","View:InventarioStatsWidget","View:EquiposPorTipoChart","View:AltasBajasPorMesChart","ViewAny:Area","View:Area","Create:Area","Update:Area","Delete:Area","DeleteAny:Area","Restore:Area","ForceDelete:Area","ForceDeleteAny:Area","RestoreAny:Area","Replicate:Area","Reorder:Area","ViewAny:Marca","View:Marca","Create:Marca","Update:Marca","Delete:Marca","DeleteAny:Marca","Restore:Marca","ForceDelete:Marca","ForceDeleteAny:Marca","RestoreAny:Marca","Replicate:Marca","Reorder:Marca","ViewAny:RazonSocial","View:RazonSocial","Create:RazonSocial","Update:RazonSocial","Delete:RazonSocial","DeleteAny:RazonSocial","Restore:RazonSocial","ForceDelete:RazonSocial","ForceDeleteAny:RazonSocial","RestoreAny:RazonSocial","Replicate:RazonSocial","Reorder:RazonSocial","ViewAny:Sucursal","View:Sucursal","Create:Sucursal","Update:Sucursal","Delete:Sucursal","DeleteAny:Sucursal","Restore:Sucursal","ForceDelete:Sucursal","ForceDeleteAny:Sucursal","RestoreAny:Sucursal","Replicate:Sucursal","Reorder:Sucursal","View:ActividadRecienteWidget","ViewAny:SuscripcionOffice365","View:SuscripcionOffice365","Create:SuscripcionOffice365","Update:SuscripcionOffice365","Delete:SuscripcionOffice365","DeleteAny:SuscripcionOffice365","Restore:SuscripcionOffice365","ForceDelete:SuscripcionOffice365","ForceDeleteAny:SuscripcionOffice365","RestoreAny:SuscripcionOffice365","Replicate:SuscripcionOffice365","Reorder:SuscripcionOffice365"]},{"name":"Colaborador","guard_name":"web","permissions":["ViewAny:EntregaDispositivo","View:EntregaDispositivo","Create:EntregaDispositivo","Update:EntregaDispositivo","Delete:EntregaDispositivo","DeleteAny:EntregaDispositivo","Restore:EntregaDispositivo","ForceDelete:EntregaDispositivo","ForceDeleteAny:EntregaDispositivo","RestoreAny:EntregaDispositivo","Replicate:EntregaDispositivo","Reorder:EntregaDispositivo","ViewAny:EquipoCelular","View:EquipoCelular","Create:EquipoCelular","Update:EquipoCelular","Delete:EquipoCelular","DeleteAny:EquipoCelular","Restore:EquipoCelular","ForceDelete:EquipoCelular","ForceDeleteAny:EquipoCelular","RestoreAny:EquipoCelular","Replicate:EquipoCelular","Reorder:EquipoCelular","ViewAny:EquipoComputo","View:EquipoComputo","Create:EquipoComputo","Update:EquipoComputo","Delete:EquipoComputo","DeleteAny:EquipoComputo","Restore:EquipoComputo","ForceDelete:EquipoComputo","ForceDeleteAny:EquipoComputo","RestoreAny:EquipoComputo","Replicate:EquipoComputo","Reorder:EquipoComputo"]}]';
        $directPermissions = '[]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
