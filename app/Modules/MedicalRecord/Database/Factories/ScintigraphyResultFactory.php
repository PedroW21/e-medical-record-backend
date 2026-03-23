<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoCintilografia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoCintilografia>
 */
final class ScintigraphyResultFactory extends Factory
{
    protected $model = ResultadoCintilografia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sss = $this->faker->numberBetween(0, 40);
        $srs = $this->faker->numberBetween(0, $sss);
        $sds = $sss - $srs;

        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'protocolo' => $this->faker->randomElement(['one_day_stress_rest', 'one_day_rest_stress', 'two_day']),
            'modalidade_estresse' => $this->faker->randomElement(['physical', 'pharmacological', 'combined']),
            'fc_max' => $this->faker->numberBetween(100, 185),
            'pct_fc_max_prevista' => $this->faker->randomFloat(2, 70.0, 100.0),
            'pa_max' => $this->faker->numberBetween(130, 210),
            'sintomas_estresse' => ['none'],
            'alteracoes_ecg_estresse' => ['none'],
            'perfusao_da_estresse' => $this->faker->randomElement(['normal', 'mild_hypoperfusion', 'moderate_hypoperfusion']),
            'perfusao_da_repouso' => 'normal',
            'perfusao_da_reversibilidade' => 'reversible',
            'perfusao_cx_estresse' => $this->faker->randomElement(['normal', 'mild_hypoperfusion']),
            'perfusao_cx_repouso' => 'normal',
            'perfusao_cx_reversibilidade' => 'reversible',
            'perfusao_cd_estresse' => 'normal',
            'perfusao_cd_repouso' => 'normal',
            'perfusao_cd_reversibilidade' => 'reversible',
            'sss' => $sss,
            'srs' => $srs,
            'sds' => $sds,
            'sds_override' => false,
            'classificacao_sds' => $sds < 4 ? 'normal' : ($sds < 8 ? 'mild_ischemia' : ($sds < 13 ? 'moderate_ischemia' : 'severe_ischemia')),
            'classificacao_sds_override' => false,
            'fe_repouso' => $this->faker->randomFloat(2, 45.0, 75.0),
            'vdf_repouso' => $this->faker->randomFloat(2, 60.0, 160.0),
            'vsf_repouso' => $this->faker->randomFloat(2, 20.0, 70.0),
            'fe_estresse' => $this->faker->randomFloat(2, 45.0, 80.0),
            'vdf_estresse' => $this->faker->randomFloat(2, 55.0, 155.0),
            'vsf_estresse' => $this->faker->randomFloat(2, 18.0, 65.0),
            'tid_presente' => false,
            'razao_tid' => $this->faker->randomFloat(4, 0.90, 1.20),
            'tid_override' => false,
            'segmentos' => null,
            'captacao_pulmonar_aumentada' => false,
            'dilatacao_vd' => false,
            'captacao_extracardiaca' => null,
            'resultado_global' => $sds < 4 ? 'normal' : 'ischemia',
            'extensao_defeito' => $sds < 4 ? null : ($sds < 8 ? 'small' : ($sds < 13 ? 'moderate' : 'large')),
            'observacoes' => null,
        ];
    }

    /**
     * Normal scintigraphy result (SDS < 4, normal perfusion).
     */
    public function normal(): static
    {
        return $this->state(fn (array $attributes) => [
            'sss' => $this->faker->numberBetween(0, 3),
            'srs' => $this->faker->numberBetween(0, 3),
            'sds' => 0,
            'classificacao_sds' => 'normal',
            'perfusao_da_estresse' => 'normal',
            'perfusao_cx_estresse' => 'normal',
            'perfusao_cd_estresse' => 'normal',
            'resultado_global' => 'normal',
            'extensao_defeito' => null,
        ]);
    }

    /**
     * Scintigraphy with significant ischemia (SDS >= 8).
     */
    public function significantIschemia(): static
    {
        return $this->state(fn (array $attributes) => [
            'sss' => $this->faker->numberBetween(12, 30),
            'srs' => $this->faker->numberBetween(0, 5),
            'sds' => $this->faker->numberBetween(8, 25),
            'classificacao_sds' => 'moderate_ischemia',
            'perfusao_da_estresse' => 'moderate_hypoperfusion',
            'perfusao_da_repouso' => 'normal',
            'resultado_global' => 'ischemia',
            'extensao_defeito' => 'moderate',
        ]);
    }
}
