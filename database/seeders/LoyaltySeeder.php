<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Badge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoyaltySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name'                    => 'First Purchase',
                'description'             => 'Complete your very first purchase.',
                'required_purchase_count' => 1,
                'required_purchase_amount'=> 0,
            ],
            [
                'name'                    => 'Shopper',
                'description'             => 'Complete 5 purchases.',
                'required_purchase_count' => 5,
                'required_purchase_amount'=> 0,
            ],
            [
                'name'                    => 'Regular Customer',
                'description'             => 'Complete 10 purchases.',
                'required_purchase_count' => 10,
                'required_purchase_amount'=> 0,
            ],
            [
                'name'                    => 'Loyal Buyer',
                'description'             => 'Spend a total of ₦10,000.',
                'required_purchase_count' => 0,
                'required_purchase_amount'=> 10000,
            ],
            [
                'name'                    => 'Big Spender',
                'description'             => 'Spend a total of ₦50,000.',
                'required_purchase_count' => 0,
                'required_purchase_amount'=> 50000,
            ],
            [
                'name'                    => 'VIP',
                'description'             => 'Complete 25 purchases.',
                'required_purchase_count' => 25,
                'required_purchase_amount'=> 0,
            ],
            [
                'name'                    => 'Champion',
                'description'             => 'Spend a total of ₦100,000.',
                'required_purchase_count' => 0,
                'required_purchase_amount'=> 100000,
            ],
            [
                'name'                    => 'Elite Member',
                'description'             => 'Complete 50 purchases.',
                'required_purchase_count' => 50,
                'required_purchase_amount'=> 0,
            ],
        ];

        foreach ($achievements as $data) {
            Achievement::firstOrCreate(['name' => $data['name']], $data);
        }


        $badges = [
            [
                'name'                        => 'Beginner',
                'description'                 => 'Unlock any 2 achievements.',
                'required_achievements_count' => 2,
                'icon'                        => '🥉',
            ],
            [
                'name'                        => 'Bronze',
                'description'                 => 'Unlock any 4 achievements.',
                'required_achievements_count' => 4,
                'icon'                        => '🥈',
            ],
            [
                'name'                        => 'Silver',
                'description'                 => 'Unlock any 6 achievements.',
                'required_achievements_count' => 6,
                'icon'                        => '🥇',
            ],
            [
                'name'                        => 'Gold',
                'description'                 => 'Unlock all 8 achievements.',
                'required_achievements_count' => 8,
                'icon'                        => '🏆',
            ],
        ];

        foreach ($badges as $data) {
            Badge::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
