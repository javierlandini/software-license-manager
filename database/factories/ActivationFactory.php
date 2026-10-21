<?php

namespace Database\Factories;

use App\Models\Activation;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activation>
 */
class ActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'machine_fingerprint' => hash('sha256', fake()->unique()->uuid()),
            'machine_name' => fake()->domainWord().'-'.fake()->randomElement(['laptop', 'desktop', 'server']),
            'ip_address' => fake()->ipv4(),
            'activated_at' => now(),
            'deactivated_at' => null,
        ];
    }

    /**
     * Indicate that the machine has been deactivated and no longer uses a seat.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }
}
