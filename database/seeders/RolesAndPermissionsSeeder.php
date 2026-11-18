<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guards = config('roles-and-permissions.guards');

        foreach ($guards as $guard => $rolesAndPermissions) {
            foreach ($rolesAndPermissions as $roleName => $permissions) {
                $role = Role::findOrCreate($roleName, $guard);

   
                $permissionModels = collect($permissions)->map(
                    fn(string $permission)=> 
                        Permission::findOrCreate($permission, $guard)
                );

                $role->syncPermissions($permissionModels);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
