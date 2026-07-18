<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles
        $roles = [
            'saas_admin' => 'SaaS Admin',
            'school_admin' => 'School Admin',
            'teacher' => 'Teacher',
            'parent' => 'Parent',
        ];

        foreach ($roles as $slug => $name) {
            \App\Models\Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        // Seed users
        $sandeep = User::firstOrCreate(
            ['email' => 'sandeep198558@gmail.com'],
            [
                'name' => 'Sandeep Rathod',
                'mobile' => '9664588677',
                'password' => bcrypt('password'),
            ]
        );
        $allRoleIds = \App\Models\Role::pluck('id')->toArray();
        $sandeep->roles()->sync($allRoleIds);

        $leena = User::firstOrCreate(
            ['email' => 'leenaadam28@gmail.com'],
            [
                'name' => 'Leena Adam',
                'mobile' => '9769409405',
                'password' => bcrypt('password'),
            ]
        );
        $leena->roles()->sync($allRoleIds);
    }
}
