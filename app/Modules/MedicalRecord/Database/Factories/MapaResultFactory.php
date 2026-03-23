<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoMapa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoMapa>
 */
final class MapaResultFactory extends Factory
{
    protected $model = ResultadoMapa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pasVigilia = $this->faker->randomFloat(2, 100.0, 140.0);
        $padVigilia = $this->faker->randomFloat(2, 60.0, 90.0);
        $pasSono = $this->faker->randomFloat(2, 90.0, 125.0);
        $padSono = $this->faker->randomFloat(2, 50.0, 80.0);

        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'pas_vigilia' => $pasVigilia,
            'pad_vigilia' => $padVigilia,
            'pas_sono' => $pasSono,
            'pad_sono' => $padSono,
            'pas_24h' => round(($pasVigilia + $pasSono) / 2, 2),
            'pad_24h' => round(($padVigilia + $padSono) / 2, 2),
            'pas_24h_override' => false,
            'pad_24h_override' => false,
            'descenso_noturno_pas' => round(($pasVigilia - $pasSono) / $pasVigilia * 100, 2),
            'descenso_noturno_pas_override' => false,
            'descenso_noturno_pad' => round(($padVigilia - $padSono) / $padVigilia * 100, 2),
            'descenso_noturno_pad_override' => false,
            'observacoes' => null,
        ];
    }

    /**
     * Hypertensive MAPA result (awake PAS >= 135).
     */
    public function hypertensive(): static
    {
        return $this->state(function (array $attributes) {
            $pasVigilia = $this->faker->randomFloat(2, 135.0, 170.0);
            $padVigilia = $this->faker->randomFloat(2, 85.0, 110.0);
            $pasSono = $this->faker->randomFloat(2, 120.0, 150.0);
            $padSono = $this->faker->randomFloat(2, 75.0, 100.0);

            return [
                'pas_vigilia' => $pasVigilia,
                'pad_vigilia' => $padVigilia,
                'pas_sono' => $pasSono,
                'pad_sono' => $padSono,
                'pas_24h' => round(($pasVigilia + $pasSono) / 2, 2),
                'pad_24h' => round(($padVigilia + $padSono) / 2, 2),
            ];
        });
    }
}
