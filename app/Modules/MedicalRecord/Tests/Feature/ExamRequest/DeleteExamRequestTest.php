<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;

it('deletes an exam request', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $examRequest = SolicitacaoExame::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}"
    );

    $response->assertOk();
    $this->assertDatabaseMissing('solicitacoes_exames', ['id' => $examRequest->id]);
});

it('rejects delete on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $examRequest = SolicitacaoExame::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}"
    );

    $response->assertStatus(409);
    $this->assertDatabaseHas('solicitacoes_exames', ['id' => $examRequest->id]);
});

it('rejects delete by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $examRequest = SolicitacaoExame::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctorB)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-requests/{$examRequest->id}"
    );

    $response->assertForbidden();
    $this->assertDatabaseHas('solicitacoes_exames', ['id' => $examRequest->id]);
});
