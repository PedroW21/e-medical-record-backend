<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('updates a lab result value', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'valor' => '14.5',
        'unidade' => 'g/dL',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '15.2']
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $labValue->id)
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.value', '15.2');

    $this->assertDatabaseHas('valores_laboratoriais', [
        'id' => $labValue->id,
        'valor' => '15.2',
    ]);
});

it('updates collection date', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'data_coleta' => '2026-03-01',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['collection_date' => '2026-03-10']
    );

    $response->assertOk()
        ->assertJsonPath('data.collection_date', '2026-03-10');

    $labValue->refresh();
    expect($labValue->data_coleta->format('Y-m-d'))->toBe('2026-03-10');
});

it('updates unit and reference range', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'unidade' => 'g/dL',
        'faixa_referencia' => '12-16',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        [
            'unit' => 'mg/dL',
            'reference_range' => '120-160',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.unit', 'mg/dL')
        ->assertJsonPath('data.reference_range', '120-160');
});

it('rejects update on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '15.2']
    );

    $response->assertStatus(409);
});

it('rejects update of value belonging to different medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuarioA->id,
        'paciente_id' => $prontuarioA->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuarioB->id}/lab-results/{$labValue->id}",
        ['value' => '15.2']
    );

    $response->assertNotFound();
});

it('rejects update by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctorB)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '15.2']
    );

    $response->assertForbidden();
});

it('rejects future collection date on update', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['collection_date' => now()->addDay()->format('Y-m-d')]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['collection_date']);
});

it('returns 404 for non-existent lab value', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/99999",
        ['value' => '15.2']
    );

    $response->assertNotFound();
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '15.2']
    );

    $response->assertUnauthorized();
});

// ─── Anexo linking ───────────────────────────────────────────────────────────

it('updates anexo_id on a single lab value via PUT', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => null,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['anexo_id' => $anexo->id]
    );

    $response->assertOk()
        ->assertJsonPath('data.anexo_id', $anexo->id);

    $this->assertDatabaseHas('valores_laboratoriais', [
        'id' => $labValue->id,
        'anexo_id' => $anexo->id,
    ]);
});

it('unlinks anexo_id on a single lab value when explicitly null', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['anexo_id' => null]
    );

    $response->assertOk()
        ->assertJsonPath('data.anexo_id', null);

    $this->assertDatabaseHas('valores_laboratoriais', [
        'id' => $labValue->id,
        'anexo_id' => null,
    ]);
});

it('keeps anexo_id unchanged when update payload omits anexo_id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '20.0']
    );

    $response->assertOk()
        ->assertJsonPath('data.anexo_id', $anexo->id);

    $this->assertDatabaseHas('valores_laboratoriais', [
        'id' => $labValue->id,
        'anexo_id' => $anexo->id,
        'valor' => '20.0',
    ]);
});
