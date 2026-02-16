<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\HorarioAtendimento;
use App\Modules\Delegation\Models\Delegacao;

it('returns empty blocks when no schedule configured', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson('/api/schedule-settings');

    $response->assertOk()
        ->assertJsonPath('data.slot_duration_minutes', 30)
        ->assertJsonPath('data.blocks', []);
});

it('returns schedule settings for the authenticated doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/schedule-settings');

    $response->assertOk()
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.day_of_week', 2)
        ->assertJsonPath('data.blocks.0.day_label', 'Terça-feira')
        ->assertJsonPath('data.blocks.0.start_time', '14:00')
        ->assertJsonPath('data.blocks.0.end_time', '18:00');
});

it('allows secretary to view delegated doctor schedule', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    $response = $this->actingAs($secretary)->getJson('/api/schedule-settings?doctor_id='.$doctor->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data.blocks');
});

it('replaces all schedule blocks on PUT', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Create an existing block that should be replaced
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 2, 'start_time' => '14:00', 'end_time' => '18:00'],
            ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '12:00'],
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data.blocks')
        ->assertJsonPath('data.blocks.0.day_of_week', 2)
        ->assertJsonPath('data.blocks.1.day_of_week', 5);

    // Old block should be gone
    $this->assertDatabaseMissing('horarios_atendimento', [
        'user_id' => $doctor->id,
        'dia_semana' => 1,
    ]);
});

it('clears all blocks when empty array is sent', function (): void {
    $doctor = User::factory()->doctor()->create();

    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.blocks', []);

    $this->assertDatabaseCount('horarios_atendimento', 0);
});

it('rejects overlapping blocks on the same day', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
            ['day_of_week' => 1, 'start_time' => '11:00', 'end_time' => '15:00'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('blocks');
});

it('rejects end_time before start_time', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '10:00'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('blocks.0.end_time');
});

it('rejects invalid day_of_week', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 7, 'start_time' => '08:00', 'end_time' => '12:00'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('blocks.0.day_of_week');
});

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/schedule-settings')->assertUnauthorized();
    $this->putJson('/api/schedule-settings', ['blocks' => []])->assertUnauthorized();
});
