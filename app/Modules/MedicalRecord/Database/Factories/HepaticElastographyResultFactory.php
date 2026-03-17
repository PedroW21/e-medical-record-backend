<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoElastografiaHepatica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoElastografiaHepatica>
 */
final class HepaticElastographyResultFactory extends Factory
{
    protected $model = ResultadoElastografiaHepatica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'fracao_gordura' => $this->faker->randomFloat(2, 0.0, 40.0),
            'tsi' => $this->faker->randomFloat(2, 0.0, 400.0),
            'kpa' => $this->faker->randomFloat(2, 2.5, 75.0),
            'observacoes' => null,
        ];
    }

    /**
     * Significant fibrosis result (kPa >= 9.5).
     */
    public function significantFibrosis(): static
    {
        return $this->state(fn (array $attributes) => [
            'kpa' => $this->faker->randomFloat(2, 9.5, 75.0),
            'observacoes' => 'Rigidez hepática elevada, compatível com fibrose significativa (F3-F4).',
        ]);
    }
}
