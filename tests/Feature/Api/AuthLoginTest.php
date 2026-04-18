<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Create personal access client after database refresh
        $exists = \DB::table('oauth_clients')
            ->where('personal_access_client', true)
            ->exists();

        if (!$exists) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--no-interaction' => true,
            ]);
        }
    }

    private function createUser($email = 'user@example.com', $password = 'password123')
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'employee',
        ]);
    }

    public function test_login_success_with_valid_credentials()
    {
        $this->createUser('user@example.com', 'password123');

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.email', 'user@example.com');
    }

    public function test_login_fails_with_wrong_email()
    {
        $this->createUser('user@example.com', 'password123');

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_login_fails_with_wrong_password()
    {
        $this->createUser('user@example.com', 'password123');

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_login_fails_without_email()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_without_password()
    {
        $this->createUser('user@example.com', 'password123');

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(401);
    }
}
