<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tracking\PostbackRequest;
use App\Traits\ApiResponse;
use App\Models\Click;
use App\Models\Link;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    use ApiResponse;

    /**
     * Handle incoming traffic from promo links
     */
    public function click($unique_hash)
    {
        $link = Link::where('unique_hash', $unique_hash)->with('campaign')->first();

        if (!$link || !$link->campaign->is_active) {
            return $this->errorResponse('Link not found or campaign inactive.', 404);
        }

        $sub_id = Str::uuid()->toString();

        Click::create([
            'link_id' => $link->id,
            'ip_address' => request()->ip(),
            'status' => 'pending',
            'sub_id' => $sub_id,
        ]);

        $redirectUrl = $link->campaign->master_url . (str_contains($link->campaign->master_url, '?') ? '&' : '?') . 'sub_id=' . $sub_id;

        return $this->successResponse([
            'hash' => $unique_hash,
            'redirect_url' => $redirectUrl
        ], 'Click logged.');
    }

    /**
     * Server-to-Server (S2S) webhook from external brand networks
     */
    public function postback(PostbackRequest $request)
    {
        $click = Click::where('sub_id', $request->sub_id)->with('link.campaign', 'link.user.group.leader')->first();

        if (!$click) {
            return $this->errorResponse('Click not found', 404);
        }

        if ($click->status !== 'pending') {
            return $this->errorResponse('Click already processed', 400);
        }

        DB::beginTransaction();
        try {
            $click->update(['status' => $request->status]);

            if ($request->status === 'approved') {
                $campaign = $click->link->campaign;
                $member = $click->link->user;
                $leader = $member->group ? $member->group->leader : null;

                $totalPayout = $campaign->total_payout;
                $memberCut = $totalPayout * ($campaign->split_member_percent / 100);
                $leaderCut = $totalPayout * ($campaign->split_leader_percent / 100);

                // Update Member Balance
                $member->increment('cleared_balance', $memberCut);
                Transaction::create([
                    'user_id' => $member->id,
                    'click_id' => $click->id,
                    'amount' => $memberCut,
                    'type' => 'commission',
                    'description' => 'Member commission for click ' . $click->sub_id,
                ]);

                // Update Leader Balance
                if ($leader) {
                    $leader->increment('cleared_balance', $leaderCut);
                    Transaction::create([
                        'user_id' => $leader->id,
                        'click_id' => $click->id,
                        'amount' => $leaderCut,
                        'type' => 'commission',
                        'description' => 'Leader commission for team member click ' . $click->sub_id,
                    ]);
                }

                // Fire affiliate's S2S postback if configured
                $this->fireAffiliatePostback($member, $click, $memberCut, $campaign->id, $request->status);
            }

            DB::commit();

            return $this->successResponse(null, 'Postback processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to process postback', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fire a server-to-server postback to the affiliate's tracking system
     */
    private function fireAffiliatePostback($member, $click, $payout, $campaignId, $status)
    {
        if (empty($member->postback_url)) {
            return;
        }

        try {
            $url = str_replace(
                ['{click_id}', '{sub_id}', '{payout}', '{campaign_id}', '{status}'],
                [$click->id, $click->sub_id, $payout, $campaignId, $status],
                $member->postback_url
            );

            Http::timeout(3)->get($url);

            Log::info("Affiliate postback fired for member {$member->id}: {$url}");
        } catch (\Exception $e) {
            Log::warning("Affiliate postback failed for member {$member->id}: " . $e->getMessage());
        }
    }
}
