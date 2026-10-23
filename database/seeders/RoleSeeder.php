<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'admin' => 'Administrador del sistema',
            'cliente' => 'Cliente que reserva canchas',
            'teller' => 'Cajero',
        ] as $name => $description) {
            Role::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
