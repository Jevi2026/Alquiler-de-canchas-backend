<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class UserSeeder extends Seeder
{
               
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(['username' => 'admin'], [
            'password' => 'Hola123',
            'name' => 'Jesus D. Villanueva',
            'gender' => 'Male',
            'birthdate' => '1987-10-05',
            'email' => 'admin@example.com',
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'status' => 'active',
        ]);
    }
}
