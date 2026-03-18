<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\MedicaoMrpa;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;

it('lists exam results for a medical record ordered by date desc', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'data' => '2026-03-10',
        'padrao' => 'normal',
    ]);

    ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'data' => '2026-03-15',
        'padrao' => 'altered',
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.date', '2026-03-15')
        ->assertJsonPath('data.0.pattern', 'altered')
        ->assertJsonPath('data.1.date', '2026-03-10')
        ->assertJsonPath('data.1.pattern', 'normal');
});

it('returns empty array when no results exist', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg"
    );

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('lists MRPA results with measurements included', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $mrpa = ResultadoMrpa::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'dias_monitorados' => 7,
        'membro' => 'right_arm',
    ]);

    MedicaoMrpa::factory()->count(4)->create([
        'resultado_mrpa_id' => $mrpa->id,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mrpa"
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.days_monitored', 7)
        ->assertJsonPath('data.0.limb', 'right_arm')
        ->assertJsonCount(4, 'data.0.measurements');
});

it('does not include results from another medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson(
        "/api/medical-records/{$prontuarioB->id}/exam-results/ecg"
    );

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects list by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->getJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg"
    );

    $response->assertForbidden();
});

it('rejects unauthenticated list', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->getJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg"
    );

    $response->assertUnauthorized();
});

it('returns 404 when medical record does not exist for list', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson(
        '/api/medical-records/99999/exam-results/ecg'
    );

    $response->assertNotFound();
});
