<?php
namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'description' => 'Handles all technology related tasks and infrastructure',
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'description' => 'Manages employee relations and organizational development',
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'description' => 'Manages financial operations and budgeting',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Handles marketing campaigns and brand management',
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'description' => 'Manages day-to-day business operations',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
