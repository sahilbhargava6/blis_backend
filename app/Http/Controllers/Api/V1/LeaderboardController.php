<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\User;
use App\Models\Group;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    use ApiResponse;

    /**
     * Get monthly leaderboard: top affiliates and top teams
     */
    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Top 5 Affiliates (members) by monthly commission earnings
        $topAffiliates = Transaction::where('type', 'commission')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereHas('user', fn($q) => $q->where('role', 'member'))
            ->select('user_id', DB::raw('SUM(amount) as total_earnings'))
            ->groupBy('user_id')
            ->orderByDesc('total_earnings')
            ->limit(5)
            ->with('user:id,name')
            ->get()
            ->map(function ($row, $index) {
                $name = $row->user->name ?? 'Unknown';
                $parts = explode(' ', $name);
                $obscured = $parts[0] . (isset($parts[1]) ? ' ' . substr($parts[1], 0, 1) . '.' : '');
                return [
                    'rank' => $index + 1,
                    'name' => $obscured,
                    'earnings' => round($row->total_earnings, 2),
                ];
            });

        // Top 5 Teams (groups) by combined member earnings this month
        $topTeams = Transaction::where('type', 'commission')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereHas('user', fn($q) => $q->where('role', 'member')->whereNotNull('group_id'))
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->select('users.group_id', DB::raw('SUM(transactions.amount) as total_earnings'))
            ->groupBy('users.group_id')
            ->orderByDesc('total_earnings')
            ->limit(5)
            ->get()
            ->map(function ($row, $index) {
                $group = Group::with('leader:id,name')->find($row->group_id);
                $groupName = $group ? $group->group_name : 'Unknown Team';
                return [
                    'rank' => $index + 1,
                    'group_name' => $groupName,
                    'leader' => $group && $group->leader ? $group->leader->name : 'N/A',
                    'earnings' => round($row->total_earnings, 2),
                ];
            });

        return $this->successResponse([
            'month' => Carbon::now()->format('F Y'),
            'top_affiliates' => $topAffiliates,
            'top_teams' => $topTeams,
        ], 'Leaderboard retrieved.');
    }
}
