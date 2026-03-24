<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;

it('lists exam requests for a medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    SolicitacaoExame::factory()->count(3)->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$prontuario->id}/exam-requests");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('does not list requests from other records', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    SolicitacaoExame::factory()->count(2)->create(['prontuario_id' => $prontuarioA->id]);
    SolicitacaoExame::factory()->count(3)->create(['prontuario_id' => $prontuarioB->id]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$prontuarioA->id}/exam-requests");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rejects list by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->getJson("/api/medical-records/{$prontuario->id}/exam-requests");

    $response->assertForbidden();
});
