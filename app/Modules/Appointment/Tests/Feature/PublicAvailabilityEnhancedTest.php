<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Models\HorarioAtendimento;

it('returns slot grid when doctor has schedule configured', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-grid']);

    // Doctor works Tuesday 14:00-16:00 (4 slots: 14:00, 14:30, 15:00, 15:30)
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    $response = $this->getJson("/api/public/schedule/dr-grid/availability?start_date={$tuesday}&end_date={$tuesday}");

    $response->assertOk()
        ->assertJsonPath('data.slot_duration_minutes', 30)
        ->assertJsonCount(1, 'data.schedule')
        ->assertJsonPath('data.schedule.0.date', $tuesday)
        ->assertJsonPath('data.schedule.0.day_of_week', 2)
        ->assertJsonPath('data.schedule.0.day_label', 'Terça-feira')
        ->assertJsonCount(4, 'data.schedule.0.slots')
        ->assertJsonPath('data.schedule.0.slots.0.time', '14:00')
        ->assertJsonPath('data.schedule.0.slots.0.available', true);
});

it('marks occupied slots as unavailable in the grid', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-occupied']);

    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    // Create a blocking appointment at 14:30
    Consulta::factory()->forDoctor($doctor)->confirmed()->create([
        'data' => $tuesday,
        'horario' => '14:30',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-occupied/availability?start_date={$tuesday}&end_date={$tuesday}");

    $response->assertOk()
        ->assertJsonPath('data.schedule.0.slots.0.time', '14:00')
        ->assertJsonPath('data.schedule.0.slots.0.available', true)
        ->assertJsonPath('data.schedule.0.slots.1.time', '14:30')
        ->assertJsonPath('data.schedule.0.slots.1.available', false);
});

it('omits days where the doctor does not work', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-omit']);

    // Doctor works only Tuesday
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');
    $wednesday = now()->next('Wednesday')->format('Y-m-d');

    $response = $this->getJson("/api/public/schedule/dr-omit/availability?start_date={$tuesday}&end_date={$wednesday}");

    $response->assertOk()
        ->assertJsonCount(1, 'data.schedule')
        ->assertJsonPath('data.schedule.0.date', $tuesday);
});

it('supports multiple blocks per day in the grid', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-multi']);

    // Morning block: 08:00-10:00 (4 slots)
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '10:00',
    ]);

    // Afternoon block: 14:00-16:00 (4 slots)
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $monday = now()->next('Monday')->format('Y-m-d');

    $response = $this->getJson("/api/public/schedule/dr-multi/availability?start_date={$monday}&end_date={$monday}");

    $response->assertOk()
        ->assertJsonCount(8, 'data.schedule.0.slots');
});

it('falls back to occupied-only response when no schedule configured', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-fallback']);
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->confirmed()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-fallback/availability?start_date={$date}&end_date={$date}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', $date)
        ->assertJsonPath('data.0.time', '10:00');
});
