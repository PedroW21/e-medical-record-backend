<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;

it('lists appointments by date range', function (): void {
    $doctor = User::factory()->doctor()->create();
    $date = now()->addDay()->format('Y-m-d');
    Consulta::factory()->forDoctor($doctor)->create(['data' => $date]);

    $response = $this->actingAs($doctor)->getJson('/api/appointments?start_date='.$date.'&end_date='.$date);

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('does not list appointments from another doctor', function (): void {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();
    $date = now()->addDay()->format('Y-m-d');
    Consulta::factory()->forDoctor($doctor1)->create(['data' => $date]);

    $response = $this->actingAs($doctor2)->getJson('/api/appointments?start_date='.$date.'&end_date='.$date);

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns empty when no appointments in range', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson('/api/appointments?start_date=2030-01-01&end_date=2030-01-31');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
