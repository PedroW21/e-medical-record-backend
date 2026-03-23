<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoDexa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoDexa>
 */
final class DexaResultFactory extends Factory
{
    protected $model = ResultadoDexa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pesoTotal = $this->faker->randomFloat(2, 50.0, 130.0);
        $gorduraTotal = $this->faker->randomFloat(2, 8000.0, 40000.0);
        $massaMagra = $this->faker->randomFloat(2, 30000.0, 80000.0);

        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'peso_total' => $pesoTotal,
            'dmo' => $this->faker->randomFloat(4, 0.800, 1.400),
            't_score' => $this->faker->randomFloat(2, -3.5, 2.5),
            'gordura_corporal_pct' => $this->faker->randomFloat(2, 15.0, 45.0),
            'gordura_total' => $gorduraTotal,
            'imc' => $this->faker->randomFloat(2, 18.5, 40.0),
            'gordura_visceral' => $this->faker->randomFloat(2, 200.0, 2000.0),
            'gordura_visceral_pct' => $this->faker->randomFloat(2, 2.0, 12.0),
            'massa_magra' => $massaMagra,
            'massa_magra_pct' => $this->faker->randomFloat(2, 50.0, 80.0),
            'fmi' => $this->faker->randomFloat(2, 3.0, 18.0),
            'ffmi' => $this->faker->randomFloat(2, 14.0, 24.0),
            'rsmi' => $this->faker->randomFloat(2, 5.0, 12.0),
            'tmr' => $this->faker->randomFloat(2, 1200.0, 2800.0),
        ];
    }

    /**
     * Osteoporosis result (T-score <= -2.5).
     */
    public function osteoporosis(): static
    {
        return $this->state(fn (array $attributes) => [
            'dmo' => $this->faker->randomFloat(4, 0.600, 0.850),
            't_score' => $this->faker->randomFloat(2, -4.0, -2.5),
        ]);
    }
}
