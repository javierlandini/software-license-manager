<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'license_id' => null,
            'status' => OrderStatus::Pending,
            'amount_cents' => fn (array $attributes) => Plan::find($attributes['plan_id'])->price_cents,
            'currency' => fn (array $attributes) => Plan::find($attributes['plan_id'])->currency,
            'external_reference' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Indicate that the order has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'external_reference' => fake()->unique()->uuid(),
            'paid_at' => now(),
        ]);
    }
}
