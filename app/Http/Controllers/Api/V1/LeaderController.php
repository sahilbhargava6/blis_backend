<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Http\Requests\Leader\InviteMemberRequest;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Link;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeaderController extends Controller
{
    use ApiResponse;

    public function stats(Request $request)
    {
        $leader = $request->user();
        $groupId = $leader->group ? $leader->group->id : Group::where('leader_id', $leader->id)->value('id');
        
        $totalGroupClicks = 0; // Requires deeper aggregation
        $groupConversionRate = 0; // Requires deeper aggregation
        $accumulatedCut = Transaction::where('user_id', $leader->id)->where('type', 'commission')->sum('amount');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyRevenue = Transaction::where('user_id', $leader->id)->where('type', 'commission')->whereDate('created_at', $date)->sum('amount');
            
            $chartData[] = [
                'date' => \Carbon\Carbon::parse($date)->format('M d'),
                'revenue' => $dailyRevenue,
            ];
        }

        return $this->successResponse([
            'total_group_clicks' => $totalGroupClicks,
            'group_conversion_rate' => $groupConversionRate,
            'accumulated_leader_cut' => $accumulatedCut,
            'chart_data' => $chartData,
        ], 'Leader stats retrieved.');
    }

    public function team(Request $request)
    {
        $leader = $request->user();
        $group = Group::where('leader_id', $leader->id)->first();

        if (!$group) {
            return $this->successResponse([], 'No team found.');
        }

        return $this->successResponse($group->members, 'Team roster retrieved.');
    }

    public function inviteMember(InviteMemberRequest $request)
    {
        $leader = $request->user();
        $group = Group::where('leader_id', $leader->id)->first();
        
        if (!$group) {
            return $this->errorResponse('Leader does not have an assigned group.');
        }

        if ($group->members()->count() >= 20) {
            return $this->errorResponse('Group has reached maximum limit of 20 members.');
        }

        // Check if there's already an active (unexpired, unused) invitation for this email
        $existingInvite = GroupInvitation::where('email', $request->email)
            ->where('is_used', false)
            ->where('expires_at', '>', \Carbon\Carbon::now())
            ->first();

        if ($existingInvite) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3004');
            $inviteLink = $frontendUrl . '/register?invite_token=' . $existingInvite->token;
            return $this->successResponse([
                'invitation' => $existingInvite,
                'invite_link' => $inviteLink
            ], 'Active invitation already exists. Link retrieved.');
        }

        $token = Str::random(40);
        $invitation = GroupInvitation::create([
            'group_id' => $group->id,
            'email' => $request->email,
            'token' => $token,
            'is_used' => false,
            'expires_at' => \Carbon\Carbon::now()->addDays(7),
        ]);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3004');
        $inviteLink = $frontendUrl . '/register?invite_token=' . $token;

        return $this->successResponse([
            'invitation' => $invitation,
            'invite_link' => $inviteLink
        ], 'Invitation generated successfully.');
    }

    public function removeMember(Request $request, $id)
    {
        $leader = $request->user();
        $group = Group::where('leader_id', $leader->id)->first();
        
        if (!$group) {
            return $this->errorResponse('Leader does not have an assigned group.');
        }

        $member = User::where('id', $id)->where('group_id', $group->id)->firstOrFail();
        $member->update(['group_id' => null]);

        return $this->successResponse(null, 'Member removed from team.');
    }

    public function campaigns()
    {
        $campaigns = Campaign::where('is_active', true)->with('assets')->get();
        return $this->successResponse($campaigns, 'Master campaigns retrieved.');
    }

    public function distributeCampaign(Request $request, $id)
    {
        $leader = $request->user();
        $group = Group::where('leader_id', $leader->id)->with('members')->first();
        
        if (!$group) {
            return $this->errorResponse('Leader does not have an assigned group.');
        }

        $campaign = Campaign::findOrFail($id);
        
        $links = [];
        foreach ($group->members as $member) {
            $links[] = Link::firstOrCreate([
                'user_id' => $member->id,
                'campaign_id' => $campaign->id,
            ], [
                'unique_hash' => Str::random(10),
            ]);
        }

        return $this->successResponse($links, 'Campaign distributed to team.');
    }

    public function earnings(Request $request)
    {
        $leader = $request->user();
        $transactions = Transaction::where('user_id', $leader->id)->orderBy('created_at', 'desc')->get();
        
        return $this->successResponse($transactions, 'Financial history ledger retrieved.');
    }
}
