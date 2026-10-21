<?php

namespace Database\Factories;

use App\Enums\LicenseEventType;
use App\Models\License;
use App\Models\LicenseEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseEvent>
 */
class LicenseEventFactory extends Factory
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
            'actor_id' => null,
            'type' => LicenseEventType::Issued,
            'metadata' => null,
        ];
    }
}
