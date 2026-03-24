<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;

it('updates items on an exam request', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $examRequest = SolicitacaoExame::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}",
        [
            'items' => [
                ['id' => 'tsh', 'name' => 'TSH ultrassensível', 'tuss_code' => '40302787', 'selected' => true],
            ],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $examRequest->id)
        ->assertJsonPath('data.items.0.id', 'tsh');
});

it('updates cid_10 and clinical indication', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $examRequest = SolicitacaoExame::factory()->create([
        'prontuario_id' => $prontuario->id,
        'cid_10' => 'I10',
        'indicacao_clinica' => 'Controle de hipertensão arterial.',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}",
        [
            'cid_10' => 'E78.5',
            'clinical_indication' => 'Investigação de dislipidemia.',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.cid_10', 'E78.5')
        ->assertJsonPath('data.clinical_indication', 'Investigação de dislipidemia.');
});

it('rejects update on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $examRequest = SolicitacaoExame::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}",
        [
            'items' => [
                ['id' => 'tsh', 'name' => 'TSH ultrassensível', 'tuss_code' => '40302787', 'selected' => true],
            ],
        ]
    );

    $response->assertStatus(409);
});

it('rejects update by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $examRequest = SolicitacaoExame::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctorB)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}",
        [
            'items' => [
                ['id' => 'tsh', 'name' => 'TSH ultrassensível', 'tuss_code' => '40302787', 'selected' => true],
            ],
        ]
    );

    $response->assertForbidden();
});
