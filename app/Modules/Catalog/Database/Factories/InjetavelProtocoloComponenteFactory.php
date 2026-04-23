<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\InjetavelProtocolo;
use App\Modules\Catalog\Models\InjetavelProtocoloComponente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InjetavelProtocoloComponente>
 */
final class InjetavelProtocoloComponenteFactory extends Factory
{
    protected $model = InjetavelProtocoloComponente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'protocolo_id' => InjetavelProtocolo::factory(),
            'ordem' => fake()->numberBetween(1, 10),
            'nome_farmaco' => fake()->words(2, true),
            'dosagem' => fake()->randomNumber(3).'mg/2mL',
            'quantidade_ampolas' => fake()->numberBetween(1, 5),
            'via' => null,
        ];
    }
}
