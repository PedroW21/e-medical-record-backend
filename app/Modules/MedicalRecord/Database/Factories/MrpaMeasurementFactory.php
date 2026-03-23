<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\MedicaoMrpa;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicaoMrpa>
 */
final class MrpaMeasurementFactory extends Factory
{
    protected $model = MedicaoMrpa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resultado_mrpa_id' => ResultadoMrpa::factory(),
            'data' => $this->faker->date(),
            'hora' => $this->faker->time('H:i'),
            'periodo' => $this->faker->randomElement(['morning', 'evening']),
            'pas' => $this->faker->numberBetween(100, 160),
            'pad' => $this->faker->numberBetween(60, 100),
        ];
    }

    /**
     * Morning measurement.
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'periodo' => 'morning',
            'hora' => $this->faker->randomElement(['06:00', '06:30', '07:00', '07:30', '08:00']),
        ]);
    }

    /**
     * Evening measurement.
     */
    public function evening(): static
    {
        return $this->state(fn (array $attributes) => [
            'periodo' => 'evening',
            'hora' => $this->faker->randomElement(['20:00', '20:30', '21:00', '21:30', '22:00']),
        ]);
    }

    /**
     * Hypertensive measurement (PAS >= 135 mmHg).
     */
    public function hypertensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'pas' => $this->faker->numberBetween(135, 180),
            'pad' => $this->faker->numberBetween(85, 115),
        ]);
    }
}
