<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Delegation\Models\Delegacao;

it('allows secretary to list delegated doctor appointments', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $date = now()->addDay()->format('Y-m-d');
    Consulta::factory()->forDoctor($doctor)->create(['data' => $date]);

    $response = $this->actingAs($secretary)->getJson('/api/appointments?start_date='.$date.'&end_date='.$date);

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('allows secretary to create appointment for delegated doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($secretary)->postJson('/api/appointments', [
        'doctor_id' => $doctor->id,
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '09:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.doctor_id', $doctor->id);
});

it('rejects secretary creating appointment without doctor_id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($secretary)->postJson('/api/appointments', [
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '09:00',
        'type' => 'consultation',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('doctor_id');
});

it('rejects secretary accessing non-delegated doctor appointments', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    // No delegation created

    $date = now()->addDay()->format('Y-m-d');

    $response = $this->actingAs($secretary)->getJson('/api/appointments?start_date='.$date.'&end_date='.$date.'&doctor_id='.$doctor->id);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('doctor_id');
});
