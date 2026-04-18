<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    private function createAndActAsUser($role = 'employee')
    {
        $user = User::factory()->create(['role' => $role]);
        Passport::actingAs($user, [$role]);
        return $user;
    }

    public function test_logout_success_when_authenticated()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/logout');

        $response->assertStatus(204);
    }

    public function test_logout_fails_without_token()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}
