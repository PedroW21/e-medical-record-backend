<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoCat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultadoCat>
 */
final class CatResultFactory extends Factory
{
    protected $model = ResultadoCat::class;

    /**
     * @return array<string, string|null>
     */
    private function arteryData(): array
    {
        return [
            'status' => $this->faker->randomElement(['pervia', 'obstrucao', null]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'cd' => $this->arteryData(),
            'ce' => $this->arteryData(),
            'da' => $this->arteryData(),
            'cx' => $this->arteryData(),
            'd1' => $this->arteryData(),
            'd2' => $this->arteryData(),
            'mge' => $this->arteryData(),
            'mgd' => $this->arteryData(),
            'dp' => $this->arteryData(),
            'stents' => [],
            'observacoes' => null,
        ];
    }

    /**
     * CAT with all arteries patent (pervia).
     */
    public function allPatent(): static
    {
        $pervia = ['status' => 'pervia'];

        return $this->state(fn (array $attributes) => [
            'cd' => $pervia,
            'ce' => $pervia,
            'da' => $pervia,
            'cx' => $pervia,
            'd1' => $pervia,
            'd2' => $pervia,
            'mge' => $pervia,
            'mgd' => $pervia,
            'dp' => $pervia,
        ]);
    }

    /**
     * CAT with a significant obstruction in the DA (anterior descending artery).
     */
    public function withDaObstruction(): static
    {
        return $this->state(fn (array $attributes) => [
            'da' => [
                'status' => 'obstrucao',
                'proximal' => ['has_obstruction' => true, 'percentage' => 70],
                'media' => ['has_obstruction' => false],
                'distal' => ['has_obstruction' => false],
            ],
            'observacoes' => 'Obstrução proximal significativa na artéria descendente anterior (70%).',
        ]);
    }
}
