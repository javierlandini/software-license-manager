<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use App\Models\License;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
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
            'plan_id' => Plan::factory()->perpetual(),
            'converted_from_license_id' => null,
            'key' => static::generateKey(),
            'type' => LicenseType::Perpetual,
            'status' => LicenseStatus::Active,
            'max_activations' => fn (array $attributes) => Plan::find($attributes['plan_id'])->max_activations,
            'starts_at' => now(),
            'expires_at' => null,
            'updates_until' => now()->addYear(),
        ];
    }

    /**
     * Generate a random license key in the XXXXX-XXXXX-XXXXX-XXXXX format.
     */
    public static function generateKey(): string
    {
        return collect(range(1, 4))
            ->map(fn () => Str::upper(Str::random(5)))
            ->implode('-');
    }

    /**
     * Indicate that the license never expires.
     */
    public function perpetual(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => Plan::factory()->perpetual(),
            'type' => LicenseType::Perpetual,
            'expires_at' => null,
            'updates_until' => now()->addYear(),
        ]);
    }

    /**
     * Indicate that the license is a recurring subscription.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => Plan::factory()->subscription(),
            'type' => LicenseType::Subscription,
            'expires_at' => now()->addMonth(),
            'updates_until' => null,
        ]);
    }

    /**
     * Indicate that the license is a time-limited trial.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => Plan::factory()->trial(),
            'type' => LicenseType::Trial,
            'expires_at' => now()->addDays(14),
            'updates_until' => null,
        ]);
    }

    /**
     * Indicate that the license has been suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Suspended,
        ]);
    }

    /**
     * Indicate that the license has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Expired,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
        ]);
    }
}
