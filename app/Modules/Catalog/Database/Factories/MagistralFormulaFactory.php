<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\MagistralCategoria;
use App\Modules\Catalog\Models\MagistralFormula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MagistralFormula>
 */
final class MagistralFormulaFactory extends Factory
{
    protected $model = MagistralFormula::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(3),
            'categoria_id' => MagistralCategoria::factory(),
            'nome' => fake()->unique()->words(3, true),
            'componentes' => [
                ['name' => fake()->word(), 'dose' => fake()->randomNumber(3).'mg'],
            ],
            'excipiente' => 'Excipiente QSP para 1 cápsula/Preparar 90 unidades',
            'posologia' => 'fazer uso ORAL de 01 unidade à noite',
            'instrucoes' => null,
            'notas' => null,
        ];
    }
}
