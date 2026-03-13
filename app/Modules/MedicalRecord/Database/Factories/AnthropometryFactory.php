<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\MedidaAntropometrica;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedidaAntropometrica>
 */
final class AnthropometryFactory extends Factory
{
    protected $model = MedidaAntropometrica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'peso' => fake()->randomFloat(2, 40, 150),
            'altura' => fake()->randomFloat(2, 140, 200),
            'imc' => fake()->randomFloat(2, 15, 45),
            'classificacao_imc' => fake()->randomElement(['underweight', 'normal', 'overweight', 'obesity_1', 'obesity_2', 'obesity_3']),
            'fc' => fake()->numberBetween(50, 120),
            'spo2' => fake()->randomFloat(2, 90, 100),
            'temperatura' => fake()->randomFloat(2, 35, 39),
            'pa_sentado_d_pas' => fake()->numberBetween(90, 180),
            'pa_sentado_d_pad' => fake()->numberBetween(60, 110),
        ];
    }

    public function withFullVitals(): static
    {
        return $this->state(fn (array $attributes) => [
            'pa_sentado_e_pas' => fake()->numberBetween(90, 180),
            'pa_sentado_e_pad' => fake()->numberBetween(60, 110),
            'pa_em_pe_d_pas' => fake()->numberBetween(90, 180),
            'pa_em_pe_d_pad' => fake()->numberBetween(60, 110),
            'circunferencia_abdominal' => fake()->randomFloat(2, 60, 130),
            'circunferencia_quadril' => fake()->randomFloat(2, 70, 140),
            'circunferencia_pescoco' => fake()->randomFloat(2, 28, 50),
        ]);
    }

    public function withSkinfolds(): static
    {
        return $this->state(fn (array $attributes) => [
            'dobra_tricipital' => fake()->randomFloat(2, 5, 35),
            'dobra_subescapular' => fake()->randomFloat(2, 5, 35),
            'dobra_suprailica' => fake()->randomFloat(2, 5, 35),
            'dobra_abdominal' => fake()->randomFloat(2, 5, 40),
            'dobra_peitoral' => fake()->randomFloat(2, 3, 25),
            'dobra_coxa' => fake()->randomFloat(2, 5, 40),
            'dobra_axilar_media' => fake()->randomFloat(2, 5, 30),
        ]);
    }

    public function withAirwayAssessment(): static
    {
        return $this->state(fn (array $attributes) => [
            'abertura_bucal' => fake()->randomFloat(2, 2, 5),
            'distancia_tireomentual' => fake()->randomFloat(2, 4, 9),
            'distancia_mentoesternal' => fake()->randomFloat(2, 8, 16),
            'deslocamento_mandibular' => fake()->randomElement(['good', 'reduced']),
        ]);
    }
}
