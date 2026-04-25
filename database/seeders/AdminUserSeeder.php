<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@taller.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('12345678'),
                'telefono' => '573000000000',
            ]
        );
        $admin->assignRole('admin');
    }
}