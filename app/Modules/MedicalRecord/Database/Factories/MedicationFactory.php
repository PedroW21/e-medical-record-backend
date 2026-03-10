<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Enums\AnvisaList;
use App\Modules\MedicalRecord\Models\Medicamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicamento>
 */
final class MedicationFactory extends Factory
{
    protected $model = Medicamento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->words(2, true),
            'principio_ativo' => fake()->word(),
            'apresentacao' => fake()->optional(0.7)->randomElement(['Comprimido 500mg', 'Cápsula 200mg', 'Solução oral 100ml']),
            'fabricante' => fake()->optional(0.5)->company(),
            'codigo_anvisa' => null,
            'lista_anvisa' => null,
            'controlado' => false,
            'ativo' => true,
        ];
    }

    public function controlled(AnvisaList $list = AnvisaList::C1): static
    {
        return $this->state(fn (array $attributes) => [
            'lista_anvisa' => $list,
            'controlado' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}
