<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class PlanSeeder extends Seeder
{
    public function run()
    {
        Plan::truncate();

        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic-monthly',
                'type' => 'monthly',
                'price' => 49.90,
                'currency' => 'euro',
                'currency_symbol' => '€',
                'features' => [
                    'Business profile',
                    '1 Event creation included',
                    'Visibility in the app & map view',
                    '1 active offer (voucher)',
                    'Participation in Nowi Points reward system',
                    'No feed post',
                    'No stamp card system',
                    'No push notifications',
                ],
            ],
            [
                'name' => 'Plus',
                'slug' => 'plus-monthly',
                'type' => 'monthly',
                'price' => 79.90,
                'currency' => 'euro',
                'currency_symbol' => '€',
                'features' => [
                    'Everything from Basic',
                    'Digital stamp card included',
                    '1 feed post per month (promotional post in public feed)',
                    'Increased visibility (priority placement over Basic)',
                    '4 Event creation included',
                    'Business dashboard & insights/statistics',
                ],
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium-monthly',
                'type' => 'monthly',
                'price' => 129.90,
                'currency' => 'euro',
                'currency_symbol' => '€',
                'features' => [
                    'Everything from Plus',
                    '6 Event creation included',
                    '2 feed posts per month',
                    '2 push notifications per month',
                    'Highest visibility (top priority placement in lists)',
                ],
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic-yearly',
                'type' => 'yearly',
                'price' => 499.90,
                'currency' => 'euro',
                'currency_symbol' => '€',
                'features' => [
                    'Business profile',
                    '1 Event creation included',
                    'Visibility in the app & map view',
                    '1 active offer (voucher)',
                    'Participation in Nowi Points reward system',
                    'No feed post',
                    'No stamp card system',
                    'No push notifications',
                ],
            ],
            [
                'name' => 'Plus',
                'slug' => 'plus-yearly',
                'type' => 'yearly',
                'price' => 799.90,
                'currency' => 'euro',
                'currency_symbol' => '€',
                'features' => [
                    'Everything from Basic',
                    'Digital stamp card included',
                    '1 feed post per month (promotional post in public feed)',
                    'Increased visibility (priority placement over Basic)',
                    '4 Event creation included',
                    'Business dashboard & insights/statistics',
                ],
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium-yearly',
                'type' => 'yearly',
                'price' => 1299.90,
                'currency' => 'euro',
                'currency_symbol' => '€',
                'features' => [
                    'Everything from Plus',
                    '6 Event creation included',
                    '2 feed posts per month',
                    '2 push notifications per month',
                    'Highest visibility (top priority placement in lists)',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            
            $product = Product::create([
                'name' => $planData['name'] . ' (' . ucfirst($planData['type']) . ')',
                'description' => implode(', ', $planData['features']),
            ]);

            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => (int)($planData['price'] * 100),
                'currency' => 'eur',
                'recurring' => [
                    'interval' => $planData['type'] === 'yearly' ? 'year' : 'month',
                ],
            ]);

            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'name' => $planData['name'],
                    'type' => $planData['type'],
                    'price' => $planData['price'],
                    'currency' => $planData['currency'],
                    'currency_symbol' => $planData['currency_symbol'],
                    'features' => json_encode($planData['features']),
                    'stripe_plan_id' => $product->id,
                    'stripe_price_id' => $price->id,
                ]
            );
        }
    }
}
