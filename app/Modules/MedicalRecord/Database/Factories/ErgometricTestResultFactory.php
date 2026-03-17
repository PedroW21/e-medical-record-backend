<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoTesteErgometrico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoTesteErgometrico>
 */
final class ErgometricTestResultFactory extends Factory
{
    protected $model = ResultadoTesteErgometrico::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fcMax = $this->faker->numberBetween(120, 185);
        $pasMax = $this->faker->numberBetween(140, 210);
        $vo2Max = $this->faker->randomFloat(2, 18.0, 55.0);

        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'protocolo' => $this->faker->randomElement(['bruce', 'bruce_modified', 'ellestad', 'naughton', 'balke', 'ramp']),
            'pct_fc_max_prevista' => $this->faker->randomFloat(2, 70.0, 100.0),
            'fc_max' => $fcMax,
            'pas_max' => $pasMax,
            'pas_pre' => $this->faker->numberBetween(110, 140),
            'vo2_max' => $vo2Max,
            'mvo2_max' => $this->faker->randomFloat(2, 8.0, 35.0),
            'deficit_cronotropico' => $this->faker->randomFloat(2, 0.0, 30.0),
            'deficit_funcional_ve' => $this->faker->randomFloat(2, 0.0, 25.0),
            'debito_cardiaco' => $this->faker->randomFloat(2, 4.0, 20.0),
            'volume_sistolico' => $this->faker->randomFloat(2, 60.0, 150.0),
            'dp_max' => $fcMax * $pasMax,
            'met_max' => $this->faker->randomFloat(2, 5.0, 16.0),
            'aptidao_cardiorrespiratoria' => $this->faker->randomElement(['low', 'moderate', 'excellent']),
            'observacoes' => null,
        ];
    }

    /**
     * Positive ergometric test (with ischemic changes).
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'aptidao_cardiorrespiratoria' => 'low',
            'pct_fc_max_prevista' => $this->faker->randomFloat(2, 70.0, 85.0),
            'observacoes' => 'Teste positivo para isquemia: infradesnivelamento do segmento ST ≥ 1mm nas derivações V4–V6 a partir do 3° estágio, reversível após 6 minutos de recuperação.',
        ]);
    }
}
