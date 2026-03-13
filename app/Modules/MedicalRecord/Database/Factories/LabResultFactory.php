<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValorLaboratorial>
 */
final class LabResultFactory extends Factory
{
    protected $model = ValorLaboratorial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'catalogo_exame_id' => 'hemo-hemoglobina',
            'nome_avulso' => null,
            'data_coleta' => $this->faker->date(),
            'valor' => (string) $this->faker->randomFloat(1, 10, 18),
            'valor_numerico' => $this->faker->randomFloat(4, 10, 18),
            'unidade' => 'g/dL',
            'faixa_referencia' => '13,5-17,5 (H) / 12,0-16,0 (M)',
            'painel_id' => null,
        ];
    }

    /**
     * Create a free-form (loose) lab result not linked to catalog.
     */
    public function loose(): static
    {
        return $this->state(fn (array $attributes) => [
            'catalogo_exame_id' => null,
            'nome_avulso' => 'Exame avulso ' . $this->faker->word(),
            'unidade' => $this->faker->randomElement(['mg/dL', 'U/L', 'mEq/L']),
            'faixa_referencia' => null,
        ]);
    }

    /**
     * Create a qualitative result.
     */
    public function qualitative(): static
    {
        return $this->state(fn (array $attributes) => [
            'catalogo_exame_id' => null,
            'nome_avulso' => 'VDRL',
            'valor' => $this->faker->randomElement(['Reagente', 'Não reagente']),
            'valor_numerico' => null,
            'unidade' => '-',
            'faixa_referencia' => 'Não reagente',
        ]);
    }
}
