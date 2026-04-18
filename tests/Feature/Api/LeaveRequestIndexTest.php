<?php

namespace Tests\Feature\Api;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LeaveRequestIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createAndActAsUser($role = 'employee')
    {
        $user = User::factory()->create(['role' => $role]);
        Passport::actingAs($user, [$role]);
        return $user;
    }

    public function test_employee_sees_only_own_leave_requests()
    {
        $employee = $this->createAndActAsUser('employee');
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        // Create leave requests for both employees
        LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Employee 1 leave',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'user_id' => $otherEmployee->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'reason' => 'Employee 2 leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.user_id', $employee->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_employee_does_not_see_other_employees_requests()
    {
        $employee = $this->createAndActAsUser('employee');
        $otherEmployee = User::factory()->create(['role' => 'employee']);

        LeaveRequest::create([
            'user_id' => $otherEmployee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Other employee leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_sees_all_leave_requests()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee1 = User::factory()->create(['role' => 'employee']);
        $employee2 = User::factory()->create(['role' => 'employee']);

        LeaveRequest::create([
            'user_id' => $employee1->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Employee 1 leave',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'user_id' => $employee2->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'reason' => 'Employee 2 leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_response_includes_user_relationship()
    {
        $admin = $this->createAndActAsUser('admin');
        $employee = User::factory()->create(['role' => 'employee']);

        LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Employee leave',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                    ],
                ],
            ]);
    }

    public function test_response_uses_pagination()
    {
        $employee = $this->createAndActAsUser('employee');

        // Create 15 leave requests (more than per_page which is 10)
        for ($i = 0; $i < 15; $i++) {
            LeaveRequest::create([
                'user_id' => $employee->id,
                'start_date' => now()->addDays($i + 1),
                'end_date' => now()->addDays($i + 2),
                'reason' => "Leave request {$i}",
                'status' => 'pending',
            ]);
        }

        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_data_is_ordered_by_latest()
    {
        $employee = $this->createAndActAsUser('employee');

        $leave1 = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'reason' => 'Leave 1',
            'status' => 'pending',
        ]);

        sleep(1); // Ensure different timestamps

        $leave2 = LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'reason' => 'Leave 2',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(200);
        $dataArray = $response->json('data');
        $this->assertEquals($leave2->id, $dataArray[0]['id']);
        $this->assertEquals($leave1->id, $dataArray[1]['id']);
    }

    public function test_index_fails_without_token()
    {
        $response = $this->getJson('/api/leave-requests');

        $response->assertStatus(401);
    }
}
