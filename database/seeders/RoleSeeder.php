<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = ['cliente', 'mecanico', 'admin'];
        foreach ($roles as $nombre) {
            Role::firstOrCreate(['nombre' => $nombre]);
        }
    }
}