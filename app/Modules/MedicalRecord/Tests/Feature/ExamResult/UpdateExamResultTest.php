<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\MedicaoMrpa;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ResultadoMapa;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;

it('updates an ECG result pattern', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'padrao' => 'normal',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}",
        ['date' => '2026-03-16', 'pattern' => 'right_deviation']
    );

    $response->assertOk()
        ->assertJsonPath('data.pattern', 'right_deviation')
        ->assertJsonPath('data.date', '2026-03-16');

    $this->assertDatabaseHas('resultados_ecg', [
        'id' => $result->id,
        'padrao' => 'right_deviation',
    ]);
});

it('partially updates a MAPA result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $result = ResultadoMapa::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'pas_vigilia' => 120.0,
        'pad_vigilia' => 78.0,
        'observacoes' => null,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mapa/{$result->id}",
        ['notes' => 'Hipertensão leve na vigília.']
    );

    $response->assertOk()
        ->assertJsonPath('data.notes', 'Hipertensão leve na vigília.')
        ->assertJsonPath('data.systolic_awake', '120.00');

    $this->assertDatabaseHas('resultados_mapa', [
        'id' => $result->id,
        'observacoes' => 'Hipertensão leve na vigília.',
        'pas_vigilia' => 120.0,
    ]);
});

it('updates an MRPA result and replaces measurements', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $mrpa = ResultadoMrpa::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'dias_monitorados' => 5,
        'membro' => 'right_arm',
    ]);

    MedicaoMrpa::factory()->count(3)->create([
        'resultado_mrpa_id' => $mrpa->id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mrpa/{$mrpa->id}",
        [
            'days_monitored' => 7,
            'limb' => 'left_arm',
            'measurements' => [
                ['date' => '2026-03-08', 'time' => '07:00', 'period' => 'morning', 'systolic' => 125, 'diastolic' => 80],
                ['date' => '2026-03-08', 'time' => '21:00', 'period' => 'evening', 'systolic' => 118, 'diastolic' => 76],
            ],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.days_monitored', 7)
        ->assertJsonPath('data.limb', 'left_arm')
        ->assertJsonCount(2, 'data.measurements');

    $this->assertDatabaseHas('resultados_mrpa', [
        'id' => $mrpa->id,
        'dias_monitorados' => 7,
        'membro' => 'left_arm',
    ]);

    $this->assertDatabaseCount('medicoes_mrpa', 2);
});

it('updates MRPA without replacing measurements when not provided', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $mrpa = ResultadoMrpa::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'dias_monitorados' => 5,
        'membro' => 'right_arm',
    ]);

    MedicaoMrpa::factory()->count(4)->create([
        'resultado_mrpa_id' => $mrpa->id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mrpa/{$mrpa->id}",
        ['observations' => 'Monitorização satisfatória.']
    );

    $response->assertOk()
        ->assertJsonCount(4, 'data.measurements');

    $this->assertDatabaseCount('medicoes_mrpa', 4);
});

it('rejects update by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'padrao' => 'normal',
    ]);

    $response = $this->actingAs($doctorB)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}",
        ['pattern' => 'altered']
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('resultados_ecg', ['id' => $result->id, 'padrao' => 'normal']);
});

it('rejects update on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'padrao' => 'normal',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}",
        ['pattern' => 'altered']
    );

    $response->assertStatus(409);
    $this->assertDatabaseHas('resultados_ecg', ['id' => $result->id, 'padrao' => 'normal']);
});

it('returns 404 for result in different medical record on update', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuarioB->id}/exam-results/ecg/{$result->id}",
        ['pattern' => 'altered']
    );

    $response->assertNotFound();
});

it('rejects unauthenticated update', function (): void {
    $prontuario = Prontuario::factory()->create();
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}",
        ['pattern' => 'altered']
    );

    $response->assertUnauthorized();
});

// ─── Anexo linking ───────────────────────────────────────────────────────────

it('unlinks on update when anexo_id is explicitly null', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}",
        ['anexo_id' => null]
    );

    $response->assertOk()
        ->assertJsonPath('data.anexo_id', null);

    $this->assertDatabaseHas('resultados_ecg', [
        'id' => $result->id,
        'anexo_id' => null,
    ]);
});

it('keeps anexo_id unchanged when update payload omits anexo_id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);
    $result = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
        'padrao' => 'normal',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg/{$result->id}",
        ['pattern' => 'altered']
    );

    $response->assertOk()
        ->assertJsonPath('data.anexo_id', $anexo->id)
        ->assertJsonPath('data.pattern', 'altered');

    $this->assertDatabaseHas('resultados_ecg', [
        'id' => $result->id,
        'anexo_id' => $anexo->id,
        'padrao' => 'altered',
    ]);
});
