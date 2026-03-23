<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoTextoLivre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoTextoLivre>
 */
final class FreeTextResultFactory extends Factory
{
    protected $model = ResultadoTextoLivre::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'tipo' => 'other',
            'texto' => $this->faker->paragraph(),
        ];
    }

    /**
     * Holter de 24 horas free-text result.
     */
    public function holter(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'holter',
            'texto' => 'Monitorização de 24 horas. Ritmo sinusal predominante com frequência média de 68 bpm. Sem pausas significativas. Extrassístoles ventriculares isoladas (< 1% do total de batimentos). Sem episódios de taquicardia ou fibrilação atrial.',
        ]);
    }

    /**
     * Polysomnography free-text result.
     */
    public function polysomnography(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'polysomnography',
            'texto' => 'Polissonografia basal. IAH = 18,4 eventos/hora — síndrome da apneia obstrutiva do sono de grau moderado. SpO2 mínima: 84%. Tempo total de sono: 6h42min. Eficiência do sono: 79,3%.',
        ]);
    }
}
