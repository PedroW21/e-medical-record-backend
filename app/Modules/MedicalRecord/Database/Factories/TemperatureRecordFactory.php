<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\RegistroTemperatura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistroTemperatura>
 */
final class TemperatureRecordFactory extends Factory
{
    protected $model = RegistroTemperatura::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'hora' => $this->faker->time('H:i'),
            'valor' => $this->faker->randomFloat(1, 35.0, 40.0),
        ];
    }

    /**
     * Febrile temperature (>= 37.8°C).
     */
    public function febrile(): static
    {
        return $this->state(fn (array $attributes) => [
            'valor' => $this->faker->randomFloat(1, 37.8, 40.0),
        ]);
    }

    /**
     * Normal temperature (36.0–37.4°C).
     */
    public function normal(): static
    {
        return $this->state(fn (array $attributes) => [
            'valor' => $this->faker->randomFloat(1, 36.0, 37.4),
        ]);
    }
}
