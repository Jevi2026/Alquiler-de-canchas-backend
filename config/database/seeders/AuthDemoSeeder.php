<?php
 
namespace Database\Seeders;
 
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
 
class AuthDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'client', 'teller'] as $name) {
            if (! Role::where('name', $name)->exists()) {
                $role = new Role();
                $role->name = $name;
                $role->save();
            }
        }
 
        $roleId = Role::where('name', 'admin')->firstOrFail()->id;
 
        foreach ([
            'admin.clase' => 'active',
            'inactivo.clase' => 'inactive',
        ] as $username => $status) {
            // Repetir este seeder restablece estas dos cuentas.
            $user = User::firstOrNew(['username' => $username]);
            $user->fill([
                'username' => $username,
                'password' => 'Clase2026!Demo',
                'name' => 'Usuario de laboratorio',
                'gender' => 'Other',
                'birthdate' => '2000-01-01',
                'email' => $username.'@example.test',
            ]);
            // Asignación confiable del seeder, no del formulario.
            $user->status = $status;
            $user->role_id = $roleId;
            $user->save();
        }
    }
}
