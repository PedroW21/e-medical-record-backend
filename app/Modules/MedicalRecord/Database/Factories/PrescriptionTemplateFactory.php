<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloPrescricao>
 */
final class PrescriptionTemplateFactory extends Factory
{
    protected $model = ModeloPrescricao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'nome' => fake()->sentence(3),
            'tags' => fake()->optional(0.5)->randomElements(['dor', 'inflamação', 'antibiótico', 'rotina'], 2),
            'subtipo' => PrescriptionSubType::Allopathic,
            'itens' => [
                [
                    'medication_name' => 'Ibuprofeno 600mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '8/8h',
                    'duration' => '5 dias',
                    'is_controlled' => false,
                ],
            ],
        ];
    }
}
