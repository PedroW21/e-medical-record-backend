<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\LabCategory;
use App\Modules\MedicalRecord\Enums\LabResultType;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

// ─── Exam-result side (ECG) ──────────────────────────────────────────────────

it('stores an ECG exam result with a linked anexo_id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-04-22', 'pattern' => 'normal', 'anexo_id' => $anexo->id]
    );

    $response->assertCreated()
        ->assertJsonPath('data.anexo_id', $anexo->id);

    $this->assertDatabaseHas('resultados_ecg', [
        'prontuario_id' => $prontuario->id,
        'padrao' => 'normal',
        'anexo_id' => $anexo->id,
    ]);
});

it('rejects anexo_id belonging to another prontuario', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuarioB->id,
        'paciente_id' => $prontuarioB->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuarioA->id}/exam-results/ecg",
        ['date' => '2026-04-22', 'pattern' => 'normal', 'anexo_id' => $anexo->id]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['anexo_id']);

    $this->assertDatabaseEmpty('resultados_ecg');
});

it('rejects anexo_id owned by another doctor', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();

    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctorB->id]);

    $anexoOfB = Anexo::factory()->create([
        'prontuario_id' => $prontuarioB->id,
        'paciente_id' => $prontuarioB->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $response = $this->actingAs($doctorA)->postJson(
        "/api/medical-records/{$prontuarioA->id}/exam-results/ecg",
        ['date' => '2026-04-22', 'pattern' => 'normal', 'anexo_id' => $anexoOfB->id]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['anexo_id']);

    $this->assertDatabaseEmpty('resultados_ecg');
});

it('rejects anexo_id already linked to another ECG result of the same prontuario', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-04-22', 'pattern' => 'normal', 'anexo_id' => $anexo->id]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['anexo_id']);

    expect(ResultadoEcg::query()->count())->toBe(1);
});

it('allows null anexo_id on store', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-04-22', 'pattern' => 'normal', 'anexo_id' => null]
    );

    $response->assertCreated()
        ->assertJsonPath('data.anexo_id', null);

    $this->assertDatabaseHas('resultados_ecg', [
        'prontuario_id' => $prontuario->id,
        'anexo_id' => null,
    ]);
});

it('unlinks anexo_id on update when passed explicitly null', function (): void {
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

it('keeps anexo_id unchanged when update payload omits the key', function (): void {
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
        ->assertJsonPath('data.pattern', 'altered')
        ->assertJsonPath('data.anexo_id', $anexo->id);

    $this->assertDatabaseHas('resultados_ecg', [
        'id' => $result->id,
        'padrao' => 'altered',
        'anexo_id' => $anexo->id,
    ]);
});

// ─── Lab side ────────────────────────────────────────────────────────────────

/**
 * Helper to seed a panel plus N analytes.
 *
 * @param  array<int, string>  $analyteIds
 */
function seedPanelWithAnalytes(string $panelId, array $analyteIds): PainelLaboratorial
{
    $panel = PainelLaboratorial::query()->create([
        'id' => $panelId,
        'nome' => ucfirst(str_replace('-', ' ', $panelId)),
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    foreach ($analyteIds as $id) {
        CatalogoExameLaboratorial::query()->create([
            'id' => $id,
            'nome' => ucfirst(str_replace('-', ' ', $id)),
            'categoria' => LabCategory::Hematologia,
            'unidade' => 'g/dL',
            'faixa_referencia' => '13,5-17,5',
            'tipo_resultado' => LabResultType::Numeric,
        ]);
    }

    return $panel;
}

it('allows the same anexo_id across multiple lab analyte rows in a single store', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    $analyteIds = ['hemo-hemoglobina', 'hemo-hematocrito', 'hemo-leucocitos'];
    $panel = seedPanelWithAnalytes('hemograma-completo', $analyteIds);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-04-22',
            'anexo_id' => $anexo->id,
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => array_map(
                        fn (string $id): array => ['analyte_id' => $id, 'value' => '10'],
                        $analyteIds,
                    ),
                ],
            ],
        ]
    );

    $response->assertCreated();

    expect(ValorLaboratorial::query()
        ->where('prontuario_id', $prontuario->id)
        ->where('anexo_id', $anexo->id)
        ->count())->toBe(3);
});

it('allows reusing the same anexo_id across multiple lab store requests', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    $panel = seedPanelWithAnalytes('hemograma-completo', ['hemo-hemoglobina']);

    $payload = [
        'date' => '2026-04-22',
        'anexo_id' => $anexo->id,
        'panels' => [
            [
                'panel_id' => $panel->id,
                'panel_name' => $panel->nome,
                'values' => [['analyte_id' => 'hemo-hemoglobina', 'value' => '14.5']],
            ],
        ],
    ];

    $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        $payload
    )->assertCreated();

    $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        $payload
    )->assertCreated();

    expect(ValorLaboratorial::query()
        ->where('prontuario_id', $prontuario->id)
        ->where('anexo_id', $anexo->id)
        ->count())->toBe(2);
});

it('rejects lab anexo_id belonging to another prontuario', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $anexoOfB = Anexo::factory()->create([
        'prontuario_id' => $prontuarioB->id,
        'paciente_id' => $prontuarioB->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    $panel = seedPanelWithAnalytes('hemograma-completo', ['hemo-hemoglobina']);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuarioA->id}/lab-results",
        [
            'date' => '2026-04-22',
            'anexo_id' => $anexoOfB->id,
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => [['analyte_id' => 'hemo-hemoglobina', 'value' => '14.5']],
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['anexo_id']);

    expect(ValorLaboratorial::query()->count())->toBe(0);
});
