<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['username' => 'admin'], [
            'password' => 'Hola123',
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'status' => 'active',
        ]);
    }
}
