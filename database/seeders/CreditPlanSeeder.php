<?php

namespace Database\Seeders;

use App\Models\CreditPlan;
use Illuminate\Database\Seeder;

class CreditPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Pack',
                'min_quantity' => 1,
                'max_quantity' => 499,
                'price_per_credit' => 12.00,
                'bonus_percentage' => 0,
                'is_active' => true,
                'sort_order' => 1,
                'badge_text' => null,
                'badge_color' => null,
            ],
            [
                'name' => 'Growth Pack',
                'min_quantity' => 500,
                'max_quantity' => 999,
                'price_per_credit' => 10.00,
                'bonus_percentage' => 10,
                'is_active' => true,
                'sort_order' => 2,
                'badge_text' => 'Popular',
                'badge_color' => 'bg-blue-500 text-white',
            ],
            [
                'name' => 'Institution Pack',
                'min_quantity' => 1000,
                'max_quantity' => 2499,
                'price_per_credit' => 8.00,
                'bonus_percentage' => 15,
                'is_active' => true,
                'sort_order' => 3,
                'badge_text' => 'Recommended',
                'badge_color' => 'bg-indigo-600 text-white',
            ],
            [
                'name' => 'Vendor Mega Pack',
                'min_quantity' => 2500,
                'max_quantity' => 4999,
                'price_per_credit' => 6.00,
                'bonus_percentage' => 20,
                'is_active' => true,
                'sort_order' => 4,
                'badge_text' => 'Best Value',
                'badge_color' => 'bg-emerald-600 text-white',
            ],
            [
                'name' => 'Commercial Press',
                'min_quantity' => 5000,
                'max_quantity' => null,
                'price_per_credit' => 4.50,
                'bonus_percentage' => 30,
                'is_active' => true,
                'sort_order' => 5,
                'badge_text' => 'Mega Volume',
                'badge_color' => 'bg-purple-600 text-white',
            ],
        ];

        foreach ($plans as $plan) {
            CreditPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
