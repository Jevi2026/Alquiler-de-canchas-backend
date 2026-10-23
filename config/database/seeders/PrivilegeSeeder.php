<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Privilege;

class PrivilegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $privilege = new Privilege ();
        $privilege ->user_id = 1;
        $privilege -> role_id = 1;
        $privilege -> save = "S";
        $privilege -> delete= "S";
        $privilege -> export = "S";
        $privilege -> save();
    }
}
