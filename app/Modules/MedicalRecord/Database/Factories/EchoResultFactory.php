<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcocardiograma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoEcocardiograma>
 */
final class EchoResultFactory extends Factory
{
    protected $model = ResultadoEcocardiograma::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ondaE = $this->faker->randomFloat(2, 50.0, 120.0);
        $ondaA = $this->faker->randomFloat(2, 40.0, 100.0);
        $relacaoEa = $ondaA > 0 ? round($ondaE / $ondaA, 4) : null;

        $eSeptal = $this->faker->randomFloat(2, 5.0, 15.0);
        $eLateral = $this->faker->randomFloat(2, 7.0, 18.0);
        $relacaoEe = $eSeptal > 0 ? round($ondaE / $eSeptal, 4) : null;

        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'tipo' => $this->faker->randomElement(['transthoracic', 'transesophageal']),
            'raiz_aorta' => $this->faker->randomFloat(2, 28.0, 42.0),
            'aorta_ascendente' => $this->faker->randomFloat(2, 28.0, 40.0),
            'arco_aortico' => $this->faker->randomFloat(2, 22.0, 35.0),
            'ae_mm' => $this->faker->randomFloat(2, 30.0, 48.0),
            'ae_ml' => $this->faker->randomFloat(2, 30.0, 80.0),
            'ae_indexado' => $this->faker->randomFloat(2, 16.0, 40.0),
            'septo' => $this->faker->randomFloat(2, 7.0, 14.0),
            'dvd' => $this->faker->randomFloat(2, 15.0, 32.0),
            'ddve' => $this->faker->randomFloat(2, 42.0, 58.0),
            'dsve' => $this->faker->randomFloat(2, 24.0, 40.0),
            'pp' => $this->faker->randomFloat(2, 7.0, 13.0),
            'erp' => $this->faker->randomFloat(4, 0.30, 0.60),
            'indice_massa_ve' => $this->faker->randomFloat(2, 70.0, 130.0),
            'fe' => $this->faker->randomFloat(2, 50.0, 75.0),
            'psap' => $this->faker->randomFloat(2, 20.0, 40.0),
            'tapse' => $this->faker->randomFloat(2, 16.0, 28.0),
            'onda_e_mitral' => $ondaE,
            'onda_a' => $ondaA,
            'relacao_e_a' => $relacaoEa,
            'relacao_e_a_override' => false,
            'e_septal' => $eSeptal,
            'e_lateral' => $eLateral,
            'relacao_e_e' => $relacaoEe,
            's_tricuspide' => $this->faker->randomFloat(2, 8.0, 16.0),
            'valva_aortica' => ['status' => 'regular'],
            'valva_mitral' => ['status' => 'regular'],
            'valva_tricuspide' => ['status' => 'regular'],
            'analise_qualitativa' => null,
        ];
    }

    /**
     * Echo with reduced ejection fraction (EF < 40%).
     */
    public function reducedEjectionFraction(): static
    {
        return $this->state(fn (array $attributes) => [
            'fe' => $this->faker->randomFloat(2, 20.0, 39.0),
            'analise_qualitativa' => 'Disfunção sistólica grave do ventrículo esquerdo com hipocinesia global difusa.',
        ]);
    }

    /**
     * Transesophageal echocardiogram.
     */
    public function transesophageal(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'transesophageal',
        ]);
    }
}
