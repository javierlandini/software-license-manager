<?php

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Enums\LicenseEventType;
use App\Enums\LicenseType;
use App\Models\Activation;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->support()->create([
            'name' => 'Support Agent',
            'email' => 'support@example.com',
        ]);

        $customers = User::factory(5)->create();

        $plans = Product::factory(2)->create()->flatMap(fn (Product $product) => [
            Plan::factory()->for($product)->perpetual()->create(['name' => 'Perpetual', 'slug' => 'perpetual']),
            Plan::factory()->for($product)->subscription(BillingInterval::Monthly)->create(['name' => 'Monthly', 'slug' => 'monthly']),
            Plan::factory()->for($product)->subscription(BillingInterval::Yearly)->create(['name' => 'Yearly', 'slug' => 'yearly']),
            Plan::factory()->for($product)->trial()->create(['name' => 'Trial', 'slug' => 'trial']),
        ]);

        foreach ($customers as $customer) {
            $plan = $plans->random();

            $licenseFactory = match ($plan->license_type) {
                LicenseType::Perpetual => License::factory()->perpetual(),
                LicenseType::Subscription => License::factory()->subscription(),
                LicenseType::Trial => License::factory()->trial(),
            };

            $license = $licenseFactory->for($customer)->create([
                'plan_id' => $plan->id,
                'max_activations' => $plan->max_activations,
            ]);

            if ($plan->license_type !== LicenseType::Trial) {
                Order::factory()->paid()->for($customer)->for($plan)->for($license)->create();
            }

            LicenseEvent::factory()->for($license)->create(['type' => LicenseEventType::Issued]);

            Activation::factory($plan->max_activations > 1 ? 2 : 1)->for($license)->create();
        }
    }
}
