<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcodopplerCarotidas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoEcodopplerCarotidas>
 */
final class CarotidEcodopplerResultFactory extends Factory
{
    protected $model = ResultadoEcodopplerCarotidas::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'espessura_intimal_carotida_comum_e' => $this->faker->randomFloat(2, 0.50, 1.20),
            'grau_estenose_carotida_comum_e' => $this->faker->randomFloat(2, 0.0, 50.0),
            'espessura_intimal_carotida_comum_d' => $this->faker->randomFloat(2, 0.50, 1.20),
            'grau_estenose_carotida_comum_d' => $this->faker->randomFloat(2, 0.0, 50.0),
            'espessura_intimal_carotida_externa_e' => $this->faker->randomFloat(2, 0.40, 1.10),
            'grau_estenose_carotida_externa_e' => $this->faker->randomFloat(2, 0.0, 40.0),
            'espessura_intimal_carotida_externa_d' => $this->faker->randomFloat(2, 0.40, 1.10),
            'grau_estenose_carotida_externa_d' => $this->faker->randomFloat(2, 0.0, 40.0),
            'espessura_intimal_bulbo_interna_e' => $this->faker->randomFloat(2, 0.50, 1.30),
            'grau_estenose_bulbo_interna_e' => $this->faker->randomFloat(2, 0.0, 60.0),
            'espessura_intimal_bulbo_interna_d' => $this->faker->randomFloat(2, 0.50, 1.30),
            'grau_estenose_bulbo_interna_d' => $this->faker->randomFloat(2, 0.0, 60.0),
            'espessura_intimal_vertebral_e' => $this->faker->randomFloat(2, 0.40, 1.00),
            'grau_estenose_vertebral_e' => $this->faker->randomFloat(2, 0.0, 30.0),
            'espessura_intimal_vertebral_d' => $this->faker->randomFloat(2, 0.40, 1.00),
            'grau_estenose_vertebral_d' => $this->faker->randomFloat(2, 0.0, 30.0),
            'observacoes' => null,
        ];
    }

    /**
     * Result with significant stenosis (>= 50% in common carotid).
     */
    public function significantStenosis(): static
    {
        return $this->state(fn (array $attributes) => [
            'grau_estenose_carotida_comum_d' => $this->faker->randomFloat(2, 50.0, 80.0),
            'observacoes' => 'Estenose hemodinamicamente significativa na carótida comum direita, com placa ateromatosa calcificada.',
        ]);
    }
}
