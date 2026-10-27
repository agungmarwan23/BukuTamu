<?php

namespace Database\Factories;

use App\Models\DutySwap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DutySwap>
 */
class DutySwapFactory extends Factory
{
    protected $model = DutySwap::class;

    public function definition(): array
    {
        return [
            'duty_date' => now()->toDateString(),
            'original_officer_id' => User::factory()->create(['role' => 'petugas'])->id,
            'replacement_officer_id' => User::factory()->create(['role' => 'petugas'])->id,
            'reason' => 'Tukar jadwal untuk keperluan mendesak',
            'swapped_by' => User::factory()->create(['role' => 'petugas'])->id,
            'created_at' => now(),
        ];
    }
}
