<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Http\Requests\Member\CustomizeLinkRequest;
use App\Http\Requests\Member\WithdrawRequest;
use App\Models\Link;
use App\Models\Click;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    use ApiResponse;

    public function stats(Request $request)
    {
        $member = $request->user();
        $linkIds = Link::where('user_id', $member->id)->pluck('id');
        
        $personalClicks = Click::whereIn('link_id', $linkIds)->count();
        $successfulConversions = Click::whereIn('link_id', $linkIds)->where('status', 'approved')->count();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyConversions = Click::whereIn('link_id', $linkIds)->where('status', 'approved')->whereDate('created_at', $date)->count();
            
            $chartData[] = [
                'date' => \Carbon\Carbon::parse($date)->format('M d'),
                'conversions' => $dailyConversions,
            ];
        }

        return $this->successResponse([
            'personal_clicks' => $personalClicks,
            'successful_conversions' => $successfulConversions,
            'pending_balance' => (float) $member->pending_balance,
            'cleared_balance' => (float) $member->cleared_balance,
            'chart_data' => $chartData,
        ], 'Member stats retrieved.');
    }

    public function campaigns(Request $request)
    {
        // For members, campaigns are those distributed by their leader (i.e. they have links for them)
        $member = $request->user();
        $campaignIds = Link::where('user_id', $member->id)->pluck('campaign_id');
        
        $campaigns = Campaign::whereIn('id', $campaignIds)->where('is_active', true)->with('assets')->get();
        return $this->successResponse($campaigns, 'Group-curated offer cards retrieved.');
    }

    public function links(Request $request)
    {
        $member = $request->user();
        $links = Link::where('user_id', $member->id)->with('campaign')->get();
        return $this->successResponse($links, 'Unique tracking sub-links retrieved.');
    }

    public function customizeLink(CustomizeLinkRequest $request)
    {
        $member = $request->user();
        $link = Link::where('id', $request->link_id)->where('user_id', $member->id)->firstOrFail();
        
        $link->update(['custom_label' => $request->custom_label]);

        return $this->successResponse($link, 'Link customized successfully.');
    }

    public function granularStats(Request $request)
    {
        $member = $request->user();
        $linkIds = Link::where('user_id', $member->id)->pluck('id');
        
        $clicks = Click::whereIn('link_id', $linkIds)->orderBy('created_at', 'desc')->paginate(50);
        return $this->successResponse($clicks, 'Granular click logs retrieved.');
    }

    public function withdraw(WithdrawRequest $request)
    {
        $member = $request->user();

        if ($member->cleared_balance < $request->amount) {
            return $this->errorResponse('Insufficient cleared balance.');
        }

        DB::beginTransaction();
        try {
            $member->decrement('cleared_balance', $request->amount);
            $member->increment('pending_balance', $request->amount);

            $payout = Payout::create([
                'user_id' => $member->id,
                'amount' => $request->amount,
                'payout_method_details' => $request->payout_method_details,
                'status' => 'pending',
            ]);

            Transaction::create([
                'user_id' => $member->id,
                'amount' => -$request->amount,
                'type' => 'withdrawal',
                'description' => 'Withdrawal request submitted',
            ]);

            DB::commit();

            return $this->successResponse($payout, 'Withdrawal request submitted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to process withdrawal', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Save or update the member's S2S postback URL
     */
    public function savePostbackUrl(Request $request)
    {
        $request->validate([
            'postback_url' => 'nullable|url|max:500',
        ]);

        $member = $request->user();
        $member->update(['postback_url' => $request->postback_url]);

        return $this->successResponse([
            'postback_url' => $member->postback_url,
        ], 'Postback URL updated.');
    }

    /**
     * Get the member's current S2S postback URL
     */
    public function getPostbackUrl(Request $request)
    {
        return $this->successResponse([
            'postback_url' => $request->user()->postback_url,
        ], 'Postback URL retrieved.');
    }
}
