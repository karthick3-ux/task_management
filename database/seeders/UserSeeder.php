<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $itDepartment = Department::where('name', 'Information Technology')->first();
        $hrDepartment = Department::where('name', 'Human Resources')->first();
        $finDepartment = Department::where('name', 'Finance')->first();

        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'department_id' => $itDepartment->id,
            'color' => '#D63B38',
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');

    
        // Create Manager
        $manager = User::create([
            'name' => 'Jane Manager',
            'email' => 'manager.jane@example.com',
            'password' => Hash::make('12345678'),
            'department_id' => $hrDepartment->id,
            'color' => '#0066FE',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        // Create User
        $user = User::create([
            'name' => 'Bob User',
            'email' => 'user.bob@example.com',
            'password' => Hash::make('12345678'),
            'department_id' => $finDepartment->id,
             'color' => '#5BC43A',
            'email_verified_at' => now(),
        ]);

           $user = User::create([
            'name' => 'Karthick',
            'email' => 'karthick153038@gmail.com',
            'password' => Hash::make('12345678'),
            'department_id' => $finDepartment->id,
             'color' => '#2a6d7f',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('staff');
    }
}
