<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoPeDiabetico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoPeDiabetico>
 */
final class DiabeticFootResultFactory extends Factory
{
    protected $model = ResultadoPeDiabetico::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nssScore = $this->faker->numberBetween(0, 9);
        $ndsScore = $this->faker->numberBetween(0, 10);
        $itbDireito = $this->faker->randomFloat(4, 0.70, 1.30);
        $itbEsquerdo = $this->faker->randomFloat(4, 0.70, 1.30);

        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'anamnese' => [
                'previous_ulcer' => false,
                'previous_amputation' => false,
                'charcot_history' => false,
                'ckd_dialysis' => false,
                'retinopathy' => $this->faker->boolean(30),
                'cvd' => $this->faker->boolean(40),
                'smoking' => $this->faker->boolean(25),
                'intermittent_claudication' => false,
                'rest_pain' => false,
            ],
            'sintomas_neuropaticos' => [
                'burning' => $this->faker->boolean(30),
                'tingling' => $this->faker->boolean(40),
                'numbness' => $this->faker->boolean(35),
                'cramps' => $this->faker->boolean(20),
                'shock_pain' => $this->faker->boolean(15),
                'location' => $this->faker->randomElement(['feet', 'calves', null]),
                'worse_at_night' => $this->faker->randomElement(['no', 'yes', null]),
                'relief_walking' => $this->faker->randomElement(['no', 'partial', null]),
            ],
            'inspecao_visual' => [
                'skin_dryness' => $this->faker->randomElement(['absent', 'mild', 'moderate']),
                'fissures' => false,
                'calluses' => $this->faker->boolean(30),
                'skin_mycosis' => false,
                'blisters' => false,
                'macerations' => false,
                'skin_color' => 'normal',
                'edema' => $this->faker->randomElement(['absent', 'mild']),
                'interdigital_issues' => [],
                'onychomycosis' => $this->faker->boolean(20),
                'onychogryphosis' => false,
                'ingrown_nail' => $this->faker->randomElement(['none', 'grade_1']),
                'subungual_hematoma' => false,
                'inadequate_nail_cut' => $this->faker->boolean(15),
                'ulcer' => ['has_active_ulcer' => false],
            ],
            'deformidades' => [
                'hallux_valgus' => $this->faker->randomElement(['none', 'mild']),
                'claw_hammer_toes' => $this->faker->randomElement(['none', 'flexible']),
                'charcot_foot' => false,
                'pes_cavus' => false,
                'pes_planus' => false,
                'bony_prominences' => $this->faker->boolean(20),
            ],
            'neurologico' => [
                'monofilament_right' => ['hallux' => true, 'met_head_1' => true, 'met_head_3' => true, 'met_head_5' => true],
                'monofilament_left' => ['hallux' => true, 'met_head_1' => true, 'met_head_3' => true, 'met_head_5' => true],
                'tuning_fork_right' => 'normal',
                'tuning_fork_left' => 'normal',
            ],
            'vascular' => [
                'vascular_right' => ['dorsalis_pedis' => 'present', 'posterior_tibial' => 'present', 'capillary_refill' => 'normal'],
                'vascular_left' => ['dorsalis_pedis' => 'present', 'posterior_tibial' => 'present', 'capillary_refill' => 'normal'],
            ],
            'termometria' => [
                'right' => ['dorsal' => $this->faker->randomFloat(1, 28.0, 35.0)],
                'left' => ['dorsal' => $this->faker->randomFloat(1, 28.0, 35.0)],
            ],
            'nss_score' => $nssScore,
            'nds_score' => $ndsScore,
            'nds_override' => false,
            'itb_direito' => $itbDireito,
            'itb_esquerdo' => $itbEsquerdo,
            'itb_direito_override' => false,
            'itb_esquerdo_override' => false,
            'tbi_direito' => null,
            'tbi_esquerdo' => null,
            'tbi_direito_override' => false,
            'tbi_esquerdo_override' => false,
            'categoria_iwgdf' => '0',
            'categoria_iwgdf_override' => false,
            'observacoes' => null,
        ];
    }

    /**
     * High-risk diabetic foot (IWGDF category 3) with neuropathy and vasculopathy.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'anamnese' => array_merge($attributes['anamnese'] ?? [], [
                'previous_ulcer' => true,
                'cvd' => true,
                'intermittent_claudication' => true,
            ]),
            'nss_score' => $this->faker->numberBetween(6, 9),
            'nds_score' => $this->faker->numberBetween(7, 10),
            'itb_direito' => $this->faker->randomFloat(4, 0.40, 0.70),
            'itb_esquerdo' => $this->faker->randomFloat(4, 0.40, 0.70),
            'categoria_iwgdf' => '3',
            'observacoes' => 'Paciente de alto risco — IWGDF categoria 3. Neuropatia periférica grave e doença arterial periférica moderada. Encaminhamento para clínica multidisciplinar de pé diabético.',
        ]);
    }
}
