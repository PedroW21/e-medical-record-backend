<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;

it('lists attachments of the given prontuario', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    Anexo::factory()->count(3)->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('excludes attachments from other prontuarios', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    Anexo::factory()->count(2)->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);
    Anexo::factory()->create([
        'prontuario_id' => $prontuarioB->id,
        'paciente_id' => $prontuarioB->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuarioA->id}/attachments"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns empty array when no attachments exist', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertOk()
        ->assertExactJson(['data' => []]);
});

it('denies listing when doctor does not own the prontuario', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->getJson(
        "/api/medical-records/{$prontuario->id}/attachments"
    );

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->getJson("/api/medical-records/{$prontuario->id}/attachments");

    $response->assertUnauthorized();
});
