<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Http\Requests\Admin\StoreCampaignRequest;
use App\Http\Requests\Admin\UpdateCampaignRequest;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Payout;
use App\Models\Click;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function stats()
    {
        $totalClicks = Click::count();
        $approvedClicks = Click::where('status', 'approved')->count();
        $conversionRate = $totalClicks > 0 ? ($approvedClicks / $totalClicks) * 100 : 0;
        
        $totalRevenue = Transaction::where('type', 'commission')->sum('amount');
        $pendingPayouts = Payout::where('status', 'pending')->sum('amount');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyRevenue = Transaction::where('type', 'commission')->whereDate('created_at', $date)->sum('amount');
            $dailyConversions = Click::where('status', 'approved')->whereDate('created_at', $date)->count();
            
            $chartData[] = [
                'date' => \Carbon\Carbon::parse($date)->format('M d'),
                'revenue' => $dailyRevenue,
                'conversions' => $dailyConversions
            ];
        }

        return $this->successResponse([
            'total_clicks' => $totalClicks,
            'conversion_rate' => round($conversionRate, 2),
            'total_revenue' => $totalRevenue,
            'pending_payouts' => $pendingPayouts,
            'chart_data' => $chartData,
        ], 'Dashboard stats retrieved.');
    }

    public function index()
    {
        $campaigns = Campaign::paginate(20);
        return $this->successResponse($campaigns, 'Campaigns listed.');
    }

    public function store(StoreCampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());
        return $this->successResponse($campaign, 'Campaign created.', 201);
    }

    public function update(UpdateCampaignRequest $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update($request->validated());
        return $this->successResponse($campaign, 'Campaign updated.');
    }

    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update(['is_active' => false]);
        return $this->successResponse(null, 'Campaign soft deleted/deactivated.');
    }

    public function groups()
    {
        $groups = Group::with('leader')->withCount('members')->get();
        return $this->successResponse($groups, 'Groups listed.');
    }

    public function storeGroup(StoreGroupRequest $request)
    {
        $group = Group::create($request->validated());
        return $this->successResponse($group, 'Group provisioned.', 201);
    }

    public function payouts()
    {
        $payouts = Payout::with('user')->orderBy('created_at', 'desc')->get();
        return $this->successResponse($payouts, 'Payouts listed.');
    }

    public function approvePayout($id)
    {
        $payout = Payout::findOrFail($id);
        
        if ($payout->status !== 'pending') {
            return $this->errorResponse('Payout already processed.');
        }

        $payout->update(['status' => 'approved']);
        // Here you would trigger the payment queue job: dispatch(new ProcessPayment($payout));

        return $this->successResponse($payout, 'Payout approved.');
    }
}
