<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Database\Factories;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Models\Consulta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consulta>
 */
final class AppointmentFactory extends Factory
{
    protected $model = Consulta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'paciente_id' => null,
            'data' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'horario' => fake()->randomElement(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00']),
            'tipo' => fake()->randomElement(AppointmentType::cases()),
            'status' => AppointmentStatus::Pending,
            'observacoes' => fake()->optional(0.3)->sentence(),
            'origem' => AppointmentOrigin::Internal,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Confirmed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
        ]);
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Requested,
            'origem' => AppointmentOrigin::Public,
            'tipo' => AppointmentType::FirstConsultation,
            'nome_solicitante' => fake()->name(),
            'telefone_solicitante' => fake()->numerify('(##) #####-####'),
            'email_solicitante' => fake()->safeEmail(),
        ]);
    }

    public function forDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $doctor->id,
        ]);
    }
}
