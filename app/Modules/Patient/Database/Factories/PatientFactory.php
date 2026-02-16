<?php

declare(strict_types=1);

namespace App\Modules\Patient\Database\Factories;

use App\Models\User;
use App\Modules\Patient\Enums\BloodType;
use App\Modules\Patient\Enums\Gender;
use App\Modules\Patient\Enums\HabitIntensity;
use App\Modules\Patient\Enums\PatientStatus;
use App\Modules\Patient\Models\Endereco;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
final class PatientFactory extends Factory
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
            'sexo' => fake()->randomElement(Gender::cases()),
            'tipo_sanguineo' => fake()->optional(0.7)->randomElement(BloodType::cases()),
            'historico_tabagismo' => fake()->optional(0.5)->randomElement(HabitIntensity::cases()),
            'historico_alcool' => fake()->optional(0.5)->randomElement(HabitIntensity::cases()),
            'status' => PatientStatus::Active,
            'ultima_consulta' => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PatientStatus::Inactive,
        ]);
    }

    public function withAddress(): static
    {
        return $this->afterCreating(function (Paciente $paciente): void {
            Endereco::factory()->create(['paciente_id' => $paciente->id]);
        });
    }
}
