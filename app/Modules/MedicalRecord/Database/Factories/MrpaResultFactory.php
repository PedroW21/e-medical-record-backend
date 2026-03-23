<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\MedicaoMrpa;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoMrpa>
 */
final class MrpaResultFactory extends Factory
{
    protected $model = ResultadoMrpa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'dias_monitorados' => $this->faker->randomElement([3, 5, 7]),
            'membro' => $this->faker->randomElement(['right_arm', 'left_arm']),
            'observacoes' => null,
        ];
    }

    /**
     * MRPA result with a given number of individual measurements.
     */
    public function withMeasurements(int $count = 6): static
    {
        return $this->afterCreating(function (ResultadoMrpa $resultado) use ($count): void {
            MedicaoMrpa::factory()
                ->count($count)
                ->create(['resultado_mrpa_id' => $resultado->id]);
        });
    }
}
