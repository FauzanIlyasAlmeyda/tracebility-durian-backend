<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    /**
     * REGISTER
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'required|email|max:191|unique:users,email',
            'username' => 'nullable|string|max:50|unique:users,username',
            'password' => 'required|min:8|confirmed',
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        $user = User::create([
            'first_name' => $request->string('first_name')->trim()->toString(),
            'last_name' => $request->string('last_name')->trim()->toString(),
            'phone' => $request->string('phone')->trim()->toString(),
            'email' => $request->string('email')->trim()->lower()->toString(),
            'username' => filled($request->username)
                ? $request->string('username')->trim()->toString()
                : null,
            'password' => $request->password,
            'role' => $request->string('role')->toString(),
        ]);

        $token = $user->createToken('flutter')->plainTextToken;

        return $this->authResponse(
            $user,
            $token,
            'Registrasi berhasil',
            201
        );
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        $identifier = $request->string('identifier')->trim()->toString();
        $normalizedIdentifier = Str::lower($identifier);

        $user = User::whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
            ->orWhere('username', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            return ApiResponse::error('Akun tidak ditemukan', 404);
        }

        if (! $user->is_active) {
            return ApiResponse::error('Akun dinonaktifkan', 403);
        }

        if (! Hash::check($request->string('password')->toString(), $user->password)) {
            return ApiResponse::error('Password salah', 401);
        }

        if ($user->role !== $request->string('role')->toString()) {
            return ApiResponse::error('Role tidak sesuai', 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user->createToken('flutter')->plainTextToken;

        return $this->authResponse($user, $token, 'Login berhasil');
    }

    /**
     * USER LOGIN
     */
    public function me(Request $request)
    {
        return ApiResponse::success([
            'user' => new UserResource($request->user()),
            'dashboard' => $this->dashboardForRole($request->user()->role),
        ], 'Session aktif');
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logout berhasil');
    }

    private function authResponse(User $user, string $token, string $message, int $status = 200)
    {
        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'dashboard' => $this->dashboardForRole($user->role),
        ], $message, $status);
    }

    private function dashboardForRole(string $role): string
    {
        return match ($role) {
            UserRole::Petani->value,
            UserRole::Pengepul->value,
            UserRole::Distributor->value,
            UserRole::Umkm->value,
            UserRole::Konsumen->value => $role,
            default => $role,
        };
    }

}
