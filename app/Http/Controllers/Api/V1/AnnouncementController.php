<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use ApiResponse;

    /**
     * Get active announcements for everyone (Members, Leaders, Admins)
     */
    public function active()
    {
        $announcements = Announcement::where('is_active', true)->orderBy('created_at', 'desc')->get();
        return $this->successResponse($announcements, 'Active announcements retrieved.');
    }

    /**
     * Get all announcements (Admin only)
     */
    public function index()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        return $this->successResponse($announcements, 'All announcements retrieved.');
    }

    /**
     * Store a new announcement (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error',
            'is_active' => 'boolean',
        ]);

        $announcement = Announcement::create($validated);
        return $this->successResponse($announcement, 'Announcement created.', 201);
    }

    /**
     * Delete/Deactivate an announcement (Admin only)
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update(['is_active' => false]);
        return $this->successResponse(null, 'Announcement deactivated.');
    }
}
