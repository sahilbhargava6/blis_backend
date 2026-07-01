<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;
use App\Models\Campaign;
use App\Models\Link;
use App\Models\Click;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@blis.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // 2. Create Leader
        $leader = User::firstOrCreate(
            ['email' => 'leader@blis.com'],
            [
                'name' => 'Top Leader',
                'password' => Hash::make('password123'),
                'role' => 'leader',
                'cleared_balance' => 1500.50,
            ]
        );

        // 3. Create Group
        $group = Group::firstOrCreate(
            ['leader_id' => $leader->id],
            [
                'group_name' => 'Alpha Team',
            ]
        );

        // 4. Create Member
        $member = User::firstOrCreate(
            ['email' => 'member@blis.com'],
            [
                'name' => 'Active Affiliate',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'group_id' => $group->id,
                'cleared_balance' => 350.00,
            ]
        );

        // 5. Create a Campaign
        $campaign = Campaign::firstOrCreate(
            ['name' => 'Summer Promo 2026'],
            [
                'description' => 'Massive summer blowout sale.',
                'master_url' => 'https://example.com/summer-sale',
                'total_payout' => 50.00,
                'split_member_percent' => 70,
                'split_leader_percent' => 30,
                'is_active' => true,
            ]
        );

        // 6. Generate a Link for the Member
        $link = Link::firstOrCreate(
            ['user_id' => $member->id, 'campaign_id' => $campaign->id],
            [
                'unique_hash' => Str::random(10),
                'custom_label' => 'Instagram Bio',
            ]
        );

        // 7. Seed some Clicks and Transactions to populate the dashboard stats
        if (Click::count() === 0) {
            for ($i = 0; $i < 10; $i++) {
                $status = $i < 7 ? 'approved' : 'pending';
                Click::create([
                    'link_id' => $link->id,
                    'ip_address' => '192.168.1.' . $i,
                    'status' => $status,
                    'sub_id' => Str::uuid()->toString(),
                ]);
            }

            // Mock some transactions for Revenue
            Transaction::create([
                'user_id' => $member->id,
                'amount' => 35.00,
                'type' => 'commission',
                'description' => 'Mock commission',
            ]);
            Transaction::create([
                'user_id' => $leader->id,
                'amount' => 15.00,
                'type' => 'commission',
                'description' => 'Mock override commission',
            ]);
        }
    }
}
