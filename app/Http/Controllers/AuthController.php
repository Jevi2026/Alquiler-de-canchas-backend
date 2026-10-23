<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
 
class AuthController extends Controller
{
    public function createAdmin(Request $request): JsonResponse
    {
        return $this->createStaff($request, 'admin');
    }

    public function createTeller(Request $request): JsonResponse
    {
        return $this->createStaff($request, 'teller');
    }

    private function createStaff(Request $request, string $roleName): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $role = \App\Models\Role::firstOrCreate(
            ['name' => $roleName],
            ['description' => $roleName === 'teller' ? 'Cajero de caja y contabilidad' : 'Administrador del sistema'],
        );

        $user = User::create([...$data, 'role_id' => $role->id, 'status' => 'active']);
        return response()->json([
            'message' => $roleName === 'teller' ? 'Cajero creado.' : 'Administrador creado.',
            'user' => $this->profile($user->load('role')),
        ], 201);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'birthdate' => ['sometimes', 'nullable', 'date', 'before:today'],
        ]);

        $clientRole = \App\Models\Role::where('name', 'cliente')->first();
        if (! $clientRole) {
            return response()->json(['message' => 'El registro no está disponible hasta configurar los roles.'], 500);
        }

        $user = User::create([...$data, 'role_id' => $clientRole->id, 'status' => 'active']);
        return response()->json([
            'message' => 'Cuenta creada. Ya puedes iniciar sesión.',
            'user' => $this->profile($user->load('role')),
        ], 201)->header('Cache-Control', 'no-store');
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
 
        $user = User::where('username', $data['username'])->first();
 
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }
 
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'El usuario no está activo.',
            ], 403);
        }
 
        $expiresAt = now()->addHour();
        $token = $user->createToken(
            $data['device_name'], ['*'], $expiresAt
        );
 
        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => $this->profile($user),
        ])->header('Cache-Control', 'no-store');
    }
 
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->profile($request->user()),
        ])->header('Cache-Control', 'no-store');
    }
 
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
 
        return response()->json([
            'message' => 'Sesión cerrada.',
        ]);
    }
 
    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'gender' => $user->gender,
            'email' => $user->email,
            'status' => $user->status,
            'role' => $user->role?->name,
        ];
    }
}
