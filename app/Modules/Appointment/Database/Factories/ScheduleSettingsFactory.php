<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Database\Factories;

use App\Models\User;
use App\Modules\Appointment\Enums\DayOfWeek;
use App\Modules\Appointment\Models\HorarioAtendimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HorarioAtendimento>
 */
final class ScheduleSettingsFactory extends Factory
{
    protected $model = HorarioAtendimento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'dia_semana' => fake()->randomElement(DayOfWeek::cases()),
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
        ];
    }

    public function forDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $doctor->id,
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'hora_inicio' => '14:00',
            'hora_fim' => '18:00',
        ]);
    }
}
