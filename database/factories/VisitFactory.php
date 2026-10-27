<?php

namespace Database\Factories;

use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purpose = $this->faker->randomElement(['pst', 'non_pst', 'pengaduan']);

        return [
            'visit_date' => now()->toDateString(),
            'channel' => 'kiosk',
            'ticket_code' => 'KIO-' . now()->format('Ymd') . '-' . $this->faker->unique()->numerify('####'),
            'queue_seq' => null,
            'queue_number' => null,
            'full_name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => '62' . $this->faker->numerify('8#########'),
            'institution' => $this->faker->company(),
            'purpose' => $purpose,
            'pst_channel' => $purpose === 'pst' ? 'offline' : null,
            'service_type' => $purpose === 'pst' ? 'Konsultasi Statistik' : null,
            'non_pst_type' => $purpose === 'non_pst' ? 'Konsultasi' : null,
            'detail' => $this->faker->sentence(),
            'complaint_title' => $purpose === 'pengaduan' ? $this->faker->sentence(4) : null,
            'photo_path' => null,
            'consent_at' => now(),
            'status' => 'menunggu',
        ];
    }

    /**
     * Visit dari saluran online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'online',
            'ticket_code' => 'ONL-' . now()->format('Ymd') . '-' . $this->faker->unique()->numerify('####'),
            'queue_seq' => null,
            'queue_number' => null,
            'pst_channel' => $attributes['purpose'] === 'pst' ? 'online' : null,
        ]);
    }
}
