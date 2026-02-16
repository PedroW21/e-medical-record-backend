<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Modules\Paciente\Models\Alergia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alergia>
 */
final class AlergiaFactory extends Factory
{
    protected $model = Alergia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->randomElement([
                'Penicilina', 'Dipirona', 'AAS', 'Sulfa', 'Latex',
                'Ibuprofeno', 'Contraste Iodado', 'Frutos do Mar',
                'Amendoim', 'Gluten', 'Nimesulida', 'Amoxicilina',
                'Cefalosporina', 'Paracetamol', 'Diclofenaco',
            ]),
        ];
    }
}
