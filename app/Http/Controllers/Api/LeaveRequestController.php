<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * Store a new leave request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:255',
            'attachment' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $currentYear = Carbon::now()->year;

        // Count leave requests for current year
        $leaveCountThisYear = LeaveRequest::where('user_id', $userId)
            ->whereYear('created_at', $currentYear)
            ->count();

        if ($leaveCountThisYear >= 12) {
            return response()->json([
                'message' => 'Batas pengajuan cuti 12 kali per tahun telah tercapai',
            ], 403);
        }

        $leaveRequest = LeaveRequest::create([
            'user_id' => $userId,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'attachment' => $validated['attachment'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($leaveRequest, 201);
    }

    /**
     * Get list of leave requests
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = LeaveRequest::query();

        if ($user->role === 'employee') {
            // Employee only sees their own requests
            $query->where('user_id', $user->id);
        } else {
            // Admin sees all requests with user info
            $query->with('user');
        }

        $leaveRequests = $query->get();

        return response()->json($leaveRequests);
    }

    /**
     * Get a specific leave request
     */
    public function show($id)
    {
        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Leave request not found'], 404);
        }

        $user = auth()->user();

        // Employee can only view their own requests
        if ($user->role === 'employee' && $leaveRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($user->role === 'admin') {
            $leaveRequest->load('user');
        }

        return response()->json($leaveRequest);
    }

    /**
     * Update status of a leave request (admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Leave request not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leaveRequest->update([
            'status' => $validated['status'],
        ]);

        return response()->json($leaveRequest);
    }
}
