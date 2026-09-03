<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Budi',
            'last_name' => 'Petani',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'username' => 'budi.petani',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'petani',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                    'dashboard',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'username' => 'budi.petani',
            'role' => 'petani',
        ]);
    }

    public function test_user_can_login_using_email_identifier(): void
    {
        $user = User::factory()->create([
            'email' => 'siti@example.com',
            'username' => 'siti.distributor',
            'role' => 'distributor',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'identifier' => 'siti@example.com',
            'password' => 'password123',
            'role' => 'distributor',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role', 'distributor');
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'andi@example.com',
            'username' => 'andi.pengepul',
            'role' => 'pengepul',
            'password' => 'password123',
        ]);

        $token = $user->createToken('testing');
        $personalAccessTokenId = $token->accessToken->id;

        $meResponse = $this->withToken($token->plainTextToken)->getJson('/api/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'andi@example.com');

        $logoutResponse = $this->withToken($token->plainTextToken)->postJson('/api/logout');

        $logoutResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing((new PersonalAccessToken())->getTable(), [
            'id' => $personalAccessTokenId,
        ]);
    }
}
