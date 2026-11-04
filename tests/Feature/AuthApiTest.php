<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $this->postJson('/api/register', [
            'name' => '研修 花子',
            'email' => 'hanako@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(); // パスワードは "password"

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_guest_cannot_access_tasks(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_response_does_not_contain_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '研修 花子',
            'email' => 'hanako@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertJsonMissingPath('user.password');
    }
}
