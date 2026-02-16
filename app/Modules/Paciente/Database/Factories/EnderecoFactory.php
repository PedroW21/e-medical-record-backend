<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Modules\Paciente\Models\Endereco;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Endereco>
 */
final class EnderecoFactory extends Factory
{
    protected $model = Endereco::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'cep' => fake()->numerify('#####-###'),
            'logradouro' => fake()->streetName(),
            'numero' => (string) fake()->buildingNumber(),
            'complemento' => fake()->optional(0.3)->secondaryAddress(),
            'bairro' => fake()->citySuffix().' '.fake()->lastName(),
            'cidade' => fake()->city(),
            'estado' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'BA', 'PE', 'CE', 'GO', 'SC']),
        ];
    }
}
