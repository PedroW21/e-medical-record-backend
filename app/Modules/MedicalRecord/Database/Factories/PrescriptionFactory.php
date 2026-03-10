<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescricao>
 */
final class PrescriptionFactory extends Factory
{
    protected $model = Prescricao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'subtipo' => PrescriptionSubType::Allopathic,
            'tipo_receita' => RecipeType::Normal,
            'tipo_receita_override' => false,
            'itens' => [
                [
                    'medication_name' => 'Paracetamol 500mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '8/8h',
                    'duration' => '5 dias',
                    'instructions' => 'Tomar após as refeições.',
                    'is_controlled' => false,
                ],
            ],
        ];
    }

    public function magistral(): static
    {
        return $this->state(fn (array $attributes) => [
            'subtipo' => PrescriptionSubType::Magistral,
            'itens' => [
                [
                    'name' => 'Fórmula manipulada vitamina D',
                    'components' => [['name' => 'Vitamina D3', 'dose' => '50.000 UI']],
                    'posology' => '1 cápsula por semana',
                ],
            ],
        ]);
    }

    public function controlled(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_receita' => RecipeType::BlueB,
            'itens' => [
                [
                    'medication_name' => 'Clonazepam 2mg',
                    'dosage' => '1 comprimido',
                    'frequency' => 'à noite',
                    'duration' => '30 dias',
                    'is_controlled' => true,
                    'control_type' => 'B1',
                ],
            ],
        ]);
    }
}
