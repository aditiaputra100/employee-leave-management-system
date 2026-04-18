<?php

namespace Tests\Feature\Api;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LeaveRequestUpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createAndActAsUser($role = 'employee')
    {
        $user = User::factory()->create(['role' => $role]);
        Passport::actingAs($user, [$role]);
        return $user;
    }

    public function test_admin_can_approve_pending_leave_request()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'approved');
    }

    public function test_admin_can_reject_pending_leave_request()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'rejected',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'rejected');
    }

    public function test_update_status_fails_with_invalid_status()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_update_status_fails_if_already_approved()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'approved',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'rejected',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Hanya pengajuan dengan status pending yang dapat diubah');
    }

    public function test_update_status_fails_if_already_rejected()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'rejected',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Hanya pengajuan dengan status pending yang dapat diubah');
    }

    public function test_update_status_returns_404_if_leave_request_not_found()
    {
        $admin = $this->createAndActAsUser('admin');

        $response = $this->patchJson('/api/leave-requests/9999/status', [
            'status' => 'approved',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Leave request not found');
    }

    public function test_employee_cannot_update_status()
    {
        $employee = $this->createAndActAsUser('employee');
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $otherEmployee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_status_fails_without_token()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Personal leave',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(401);
    }
}
