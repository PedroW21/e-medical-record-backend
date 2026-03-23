<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\MedicaoMrpa;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;

it('deletes an exam result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}"
    );

    $response->assertOk()
        ->assertJsonPath('message', 'Resultado de ECG excluído com sucesso.');

    $this->assertDatabaseMissing('resultados_ecg', ['id' => $result->id]);
});

it('deletes an MRPA result and cascades to measurements', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $mrpa = ResultadoMrpa::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    MedicaoMrpa::factory()->count(4)->create([
        'resultado_mrpa_id' => $mrpa->id,
    ]);

    $this->assertDatabaseCount('medicoes_mrpa', 4);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mrpa/{$mrpa->id}"
    );

    $response->assertOk()
        ->assertJsonPath('message', 'Resultado de MRPA excluído com sucesso.');

    $this->assertDatabaseMissing('resultados_mrpa', ['id' => $mrpa->id]);
    $this->assertDatabaseCount('medicoes_mrpa', 0);
});

it('rejects delete by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctorB)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}"
    );

    $response->assertForbidden();
    $this->assertDatabaseHas('resultados_ecg', ['id' => $result->id]);
});

it('rejects delete on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}"
    );

    $response->assertStatus(409);
    $this->assertDatabaseHas('resultados_ecg', ['id' => $result->id]);
});

it('returns 404 for non-existent result on delete', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/99999"
    );

    $response->assertNotFound();
});

it('returns 404 for result in different medical record on delete', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuarioB->id}/exam-results/ecg/{$result->id}"
    );

    $response->assertNotFound();
    $this->assertDatabaseHas('resultados_ecg', ['id' => $result->id]);
});

it('rejects unauthenticated delete', function (): void {
    $prontuario = Prontuario::factory()->create();
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->deleteJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}"
    );

    $response->assertUnauthorized();
    $this->assertDatabaseHas('resultados_ecg', ['id' => $result->id]);
});
