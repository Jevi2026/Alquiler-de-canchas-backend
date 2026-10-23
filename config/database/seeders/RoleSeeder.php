<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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
