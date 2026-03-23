<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoEcg>
 */
final class EcgResultFactory extends Factory
{
    protected $model = ResultadoEcg::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'padrao' => $this->faker->randomElement(['normal', 'right_deviation', 'left_deviation']),
            'texto_personalizado' => null,
        ];
    }

    /**
     * ECG with altered pattern and descriptive custom text.
     */
    public function altered(): static
    {
        return $this->state(fn (array $attributes) => [
            'padrao' => 'altered',
            'texto_personalizado' => 'Sobrecarga ventricular esquerda com alterações inespecíficas de repolarização.',
        ]);
    }
}
