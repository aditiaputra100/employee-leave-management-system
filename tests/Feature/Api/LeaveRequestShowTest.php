<?php

namespace Tests\Feature\Api;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LeaveRequestShowTest extends TestCase
{
    use RefreshDatabase;

    private function createAndActAsUser($role = 'employee')
    {
        $user = User::factory()->create(['role' => $role]);
        Passport::actingAs($user, [$role]);
        return $user;
    }

    public function test_employee_can_view_own_leave_request()
    {
        $employee = $this->createAndActAsUser('employee');

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/leave-requests/{$leaveRequest->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $leaveRequest->id)
            ->assertJsonPath('user_id', $employee->id);
    }

    public function test_employee_cannot_view_other_employee_leave_request()
    {
        $employee = $this->createAndActAsUser('employee');
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $otherEmployee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Other employee leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/leave-requests/{$leaveRequest->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_admin_can_view_any_leave_request()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Employee leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/leave-requests/{$leaveRequest->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $leaveRequest->id);
    }

    public function test_admin_response_includes_user_relationship()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Employee leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/leave-requests/{$leaveRequest->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user_id',
                'start_date',
                'end_date',
                'reason',
                'status',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);
    }

    public function test_show_returns_404_if_leave_request_not_found()
    {
        $this->createAndActAsUser('employee');

        $response = $this->getJson('/api/leave-requests/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Leave request not found');
    }

    public function test_show_fails_without_token()
    {
        $response = $this->getJson('/api/leave-requests/1');

        $response->assertStatus(401);
    }
}
