<?php

namespace Database\Factories;

use App\Models\DutySchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DutySchedule>
 */
class DutyScheduleFactory extends Factory
{
    protected $model = DutySchedule::class;

    public function definition(): array
    {
        return [
            'duty_date' => now()->toDateString(),
            'officer_id' => User::factory()->create(['role' => 'petugas'])->id,
            'created_by' => null,
        ];
    }
}
