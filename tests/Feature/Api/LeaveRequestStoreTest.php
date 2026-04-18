<?php

namespace Tests\Feature\Api;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LeaveRequestStoreTest extends TestCase
{
    use RefreshDatabase;

    private function createAndActAsUser($role = 'employee')
    {
        $user = User::factory()->create(['role' => $role]);
        Passport::actingAs($user, [$role]);
        return $user;
    }

    public function test_store_success_with_valid_data_and_file()
    {
        Storage::fake('public');

        $this->createAndActAsUser();

        $file = UploadedFile::fake()->create('leave.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
            'attachment' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');
    }

    public function test_store_success_without_attachment()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');
    }

    public function test_store_fails_without_start_date()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/leave-requests', [
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_date');
    }

    public function test_store_fails_without_end_date()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_store_fails_without_reason()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_store_fails_if_start_date_is_before_today()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->subDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_date');
    }

    public function test_store_fails_if_end_date_is_before_start_date()
    {
        $this->createAndActAsUser();

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_store_fails_if_attachment_is_not_pdf()
    {
        Storage::fake('public');

        $this->createAndActAsUser();

        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
            'attachment' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('attachment');
    }

    public function test_store_fails_if_attachment_exceeds_5mb()
    {
        Storage::fake('public');

        $this->createAndActAsUser();

        $file = UploadedFile::fake()->create('large.pdf', 5200, 'application/pdf');

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
            'attachment' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('attachment');
    }

    public function test_store_fails_if_reached_12_limit_in_current_year()
    {
        $user = $this->createAndActAsUser();

        // Create 12 leave requests in current year
        for ($i = 0; $i < 12; $i++) {
            LeaveRequest::create([
                'user_id' => $user->id,
                'start_date' => now()->addDays($i + 1),
                'end_date' => now()->addDays($i + 2),
                'reason' => "Leave request {$i}",
                'status' => 'pending',
            ]);
        }

        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(20)->format('Y-m-d'),
            'end_date' => now()->addDays(21)->format('Y-m-d'),
            'reason' => 'Over limit',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Batas pengajuan cuti 12 kali per tahun telah tercapai');
    }

    public function test_store_success_if_12_requests_in_previous_year()
    {
        $user = $this->createAndActAsUser();

        // Manually create 12 leave requests in previous year by manipulating data
        $pastDate = now()->subYear();
        for ($i = 0; $i < 12; $i++) {
            DB::table('leave_requests')->insert([
                'user_id' => $user->id,
                'start_date' => $pastDate->addDays($i + 1),
                'end_date' => $pastDate->addDays($i + 2),
                'reason' => "Leave request {$i}",
                'status' => 'pending',
                'created_at' => $pastDate,
                'updated_at' => $pastDate,
            ]);
        }

        // Now create a new request in current year (should work)
        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'New year new limit',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_fails_without_token()
    {
        $response = $this->postJson('/api/leave-requests', [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(401);
    }
}
