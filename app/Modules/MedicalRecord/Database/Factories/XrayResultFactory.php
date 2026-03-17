<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoRx;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoRx>
 */
final class XrayResultFactory extends Factory
{
    protected $model = ResultadoRx::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'padrao' => $this->faker->randomElement(['normal', 'poor_quality']),
            'texto_personalizado' => null,
        ];
    }

    /**
     * X-ray with altered findings and descriptive custom text.
     */
    public function altered(): static
    {
        return $this->state(fn (array $attributes) => [
            'padrao' => 'altered',
            'texto_personalizado' => 'Aumento da área cardíaca e redistribuição vascular pulmonar.',
        ]);
    }
}
