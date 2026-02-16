<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Models\User;
use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use App\Modules\Paciente\Models\Endereco;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
final class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome' => fake()->name(),
            'cpf' => fake()->unique()->numerify('###.###.###-##'),
            'telefone' => fake()->numerify('(##) #####-####'),
            'email' => fake()->optional(0.8)->safeEmail(),
            'data_nascimento' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'sexo' => fake()->randomElement(Sexo::cases()),
            'tipo_sanguineo' => fake()->optional(0.7)->randomElement(TipoSanguineo::cases()),
            'historico_tabagismo' => fake()->optional(0.5)->randomElement(IntensidadeHabito::cases()),
            'historico_alcool' => fake()->optional(0.5)->randomElement(IntensidadeHabito::cases()),
            'status' => StatusPaciente::Active,
            'ultima_consulta' => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPaciente::Inactive,
        ]);
    }

    public function withEndereco(): static
    {
        return $this->afterCreating(function (Paciente $paciente): void {
            Endereco::factory()->create(['paciente_id' => $paciente->id]);
        });
    }
}
