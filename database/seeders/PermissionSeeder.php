<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Department permissions
            'view_departments',
            'create_departments',
            'edit_departments',
            'delete_departments',
            
            // Project permissions (for future use)
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',
            
            'view_tasks',
            'create_tasks',
            'edit_tasks',
            'delete_tasks',
            'assign_task_users',
            'view_update_history',

            'manage_roles',
            'create_roles',

            'manage_task_assignments',
             
            'view_employee_reports', 
            'view_project_report'      
           
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        //$superAdmin->givePermissionTo(Permission::all());

     
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view_users', 'create_users', 'edit_users',
            'view_departments',
            'view_projects', 'create_projects', 'edit_projects', 
            'view_tasks', 'create_tasks', 'edit_tasks',
           
        ]);

        $user = Role::create(['name' => 'staff']);
        $user->givePermissionTo([
            'view_tasks', 'create_tasks', 'edit_tasks',
        ]);
    }
}