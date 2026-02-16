<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Models\HorarioAtendimento;

it('allows appointment within working hours', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works Tuesday 14:00-18:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    // Find the next Tuesday
    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $tuesday,
        'time' => '15:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});

it('rejects appointment outside working hours', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works Tuesday 14:00-18:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    // Try to book on Tuesday morning (outside working hours)
    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $tuesday,
        'time' => '09:00',
        'type' => 'consultation',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('rejects appointment on a day the doctor does not work', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works only Tuesday
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    // Try to book on Wednesday
    $wednesday = now()->next('Wednesday')->format('Y-m-d');

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $wednesday,
        'time' => '15:00',
        'type' => 'consultation',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('allows appointment when no schedule is configured', function (): void {
    $doctor = User::factory()->doctor()->create();
    // No schedule settings — no restrictions

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '10:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});

it('rejects public booking outside working hours', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-horario']);

    // Doctor works Friday 08:00-12:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 5,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    // Try to book on Friday afternoon (outside working hours)
    $friday = now()->next('Friday')->format('Y-m-d');

    $response = $this->postJson('/api/public/schedule/dr-horario/book', [
        'nome' => 'Maria da Silva',
        'telefone' => '(11) 99999-0000',
        'email' => 'maria@email.com',
        'data' => $friday,
        'horario' => '15:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('validates working hours on appointment update when time changes', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works Tuesday 14:00-18:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    // Create appointment within working hours
    $appointment = Consulta::factory()->forDoctor($doctor)->create([
        'data' => $tuesday,
        'horario' => '15:00',
    ]);

    // Try to update to a time outside working hours
    $response = $this->actingAs($doctor)->putJson("/api/appointments/{$appointment->id}", [
        'time' => '09:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});
