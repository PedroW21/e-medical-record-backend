<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;

it('returns occupied time slots for a doctor by slug', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-teste']);
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->confirmed()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-teste/availability?start_date={$date}&end_date={$date}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', $date)
        ->assertJsonPath('data.0.time', '10:00');
});

it('does not include cancelled slots in availability', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-cancel']);
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->cancelled()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-cancel/availability?start_date={$date}&end_date={$date}");

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not include requested slots in availability', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-requested']);
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->requested()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-requested/availability?start_date={$date}&end_date={$date}");

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns 404 for invalid slug', function (): void {
    $response = $this->getJson('/api/public/schedule/slug-invalido/availability');

    $response->assertNotFound();
});
