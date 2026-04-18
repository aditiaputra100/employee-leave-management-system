<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('leave_requests')->insert([
            [
                'user_id' => 2, // employee@example.com
                'start_date' => '2026-05-01 00:00:00',
                'end_date' => '2026-05-05 00:00:00',
                'reason' => 'Family vacation',
                'attachment' => null,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'start_date' => '2026-06-10 00:00:00',
                'end_date' => '2026-06-12 00:00:00',
                'reason' => 'Medical appointment',
                'attachment' => null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'start_date' => '2026-04-20 00:00:00',
                'end_date' => '2026-04-21 00:00:00',
                'reason' => 'Personal business',
                'attachment' => null,
                'status' => 'rejected',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
