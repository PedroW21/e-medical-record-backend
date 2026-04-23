<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Enums\InjectableProtocolRoute;
use App\Modules\Catalog\Models\CategoriaTerapeutica;
use App\Modules\Catalog\Models\Farmacia;
use App\Modules\Catalog\Models\InjetavelProtocolo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InjetavelProtocolo>
 */
final class InjetavelProtocoloFactory extends Factory
{
    protected $model = InjetavelProtocolo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(3),
            'farmacia_id' => Farmacia::factory(),
            'categoria_terapeutica_id' => CategoriaTerapeutica::factory(),
            'nome' => fake()->unique()->words(3, true),
            'via' => fake()->randomElement(InjectableProtocolRoute::cases())->value,
            'notas_aplicacao' => null,
        ];
    }
}
