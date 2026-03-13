<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prontuario>
 */
final class MedicalRecordFactory extends Factory
{
    protected $model = Prontuario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'user_id' => User::factory()->doctor(),
            'tipo' => MedicalRecordType::FirstVisit,
            'status' => MedicalRecordStatus::Draft,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MedicalRecordStatus::Finalized,
            'finalizado_em' => now(),
        ]);
    }

    public function followUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => MedicalRecordType::FollowUp,
        ]);
    }

    public function preAnesthetic(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => MedicalRecordType::PreAnesthetic,
        ]);
    }

    public function withPhysicalExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'exame_fisico' => [
                'cardiac' => ['is_normal' => true],
                'respiratory' => ['is_normal' => true],
                'lower_limbs' => [
                    'varicose_veins' => false,
                    'edema' => false,
                    'lymphedema' => false,
                    'ulcer' => false,
                    'asymmetry' => false,
                    'sensitivity_alteration' => false,
                    'motricity_alteration' => false,
                ],
                'dentition' => ['status' => 'regular'],
                'gums' => ['status' => 'regular'],
            ],
        ]);
    }

    public function withProblemList(): static
    {
        return $this->state(fn (array $attributes) => [
            'lista_problemas' => [
                'selected_problems' => [
                    [
                        'problem_id' => 'has',
                        'label' => 'Hipertensão Arterial Sistêmica',
                        'category' => 'metabolic',
                        'is_custom' => false,
                    ],
                ],
                'custom_problems' => [],
            ],
        ]);
    }

    public function withConduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'conduta' => [
                'sleep_hygiene' => true,
                'sleep_default_text' => 'Manter higiene do sono adequada.',
                'sleep_observations' => null,
                'diets' => [],
                'physical_activity' => [
                    'default_text' => 'Atividade física regular conforme orientação.',
                ],
                'xenobiotics_restriction' => false,
                'xenobiotics_default_text' => 'Evitar tabagismo e etilismo.',
                'xenobiotics_observations' => null,
                'medication_compliance' => true,
                'medication_default_text' => 'Manter adesão medicamentosa.',
                'medication_observations' => null,
            ],
        ]);
    }
}
