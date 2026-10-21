<?php

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Enums\LicenseType;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'product_id' => Product::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'license_type' => LicenseType::Perpetual,
            'price_cents' => fake()->numberBetween(1000, 50000),
            'currency' => 'USD',
            'billing_interval' => null,
            'trial_days' => null,
            'updates_months' => 12,
            'max_activations' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the plan sells perpetual licenses.
     */
    public function perpetual(): static
    {
        return $this->state(fn (array $attributes) => [
            'license_type' => LicenseType::Perpetual,
            'billing_interval' => null,
            'trial_days' => null,
            'updates_months' => 12,
        ]);
    }

    /**
     * Indicate that the plan sells subscription licenses.
     */
    public function subscription(BillingInterval $interval = BillingInterval::Monthly): static
    {
        return $this->state(fn (array $attributes) => [
            'license_type' => LicenseType::Subscription,
            'billing_interval' => $interval,
            'trial_days' => null,
            'updates_months' => null,
        ]);
    }

    /**
     * Indicate that the plan grants free trial licenses.
     */
    public function trial(int $days = 14): static
    {
        return $this->state(fn (array $attributes) => [
            'license_type' => LicenseType::Trial,
            'price_cents' => 0,
            'billing_interval' => null,
            'trial_days' => $days,
            'updates_months' => null,
        ]);
    }
}
