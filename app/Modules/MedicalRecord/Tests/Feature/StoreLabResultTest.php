<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\LabCategory;
use App\Modules\MedicalRecord\Enums\LabResultType;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;

it('stores panel-based lab results', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    $analyte = CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => LabCategory::Hematologia,
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => LabResultType::Numeric,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'is_custom' => false,
                    'values' => [
                        ['analyte_id' => $analyte->id, 'value' => '14.5'],
                    ],
                ],
            ],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.0.date', '2026-03-10')
        ->assertJsonPath('data.0.panels.0.panel_id', 'hemograma-completo')
        ->assertJsonPath('data.0.panels.0.panel_name', 'Hemograma Completo')
        ->assertJsonPath('data.0.panels.0.values.0.analyte_id', 'hemo-hemoglobina')
        ->assertJsonPath('data.0.panels.0.values.0.value', '14.5')
        ->assertJsonPath('data.0.loose', []);

    $this->assertDatabaseHas('valores_laboratoriais', [
        'prontuario_id' => $prontuario->id,
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor' => '14.5',
        'painel_id' => 'hemograma-completo',
    ]);
});

it('stores loose (free-form) lab results', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'loose' => [
                [
                    'name' => 'VDRL',
                    'value' => 'Não reagente',
                    'unit' => '-',
                    'reference_range' => 'Não reagente',
                ],
            ],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.0.date', '2026-03-10')
        ->assertJsonPath('data.0.panels', [])
        ->assertJsonPath('data.0.loose.0.name', 'VDRL')
        ->assertJsonPath('data.0.loose.0.value', 'Não reagente')
        ->assertJsonPath('data.0.loose.0.unit', '-')
        ->assertJsonPath('data.0.loose.0.reference_range', 'Não reagente');

    $this->assertDatabaseHas('valores_laboratoriais', [
        'prontuario_id' => $prontuario->id,
        'nome_avulso' => 'VDRL',
        'valor' => 'Não reagente',
        'painel_id' => null,
    ]);
});

it('stores panels and loose entries in a single request', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'bioquimica-basica',
        'nome' => 'Bioquímica Básica',
        'categoria' => LabCategory::Bioquimica,
        'subsecoes' => [],
    ]);

    $analyte = CatalogoExameLaboratorial::query()->create([
        'id' => 'bio-glicose',
        'nome' => 'Glicose',
        'categoria' => LabCategory::Bioquimica,
        'unidade' => 'mg/dL',
        'faixa_referencia' => '70-99',
        'tipo_resultado' => LabResultType::Numeric,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => [
                        ['analyte_id' => $analyte->id, 'value' => '92'],
                    ],
                ],
            ],
            'loose' => [
                [
                    'name' => 'PCR',
                    'value' => '0.3',
                    'unit' => 'mg/L',
                    'reference_range' => '< 5',
                ],
            ],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.0.date', '2026-03-10')
        ->assertJsonCount(1, 'data.0.panels')
        ->assertJsonCount(1, 'data.0.loose');

    $this->assertDatabaseCount('valores_laboratoriais', 2);
});

it('rejects store on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    $analyte = CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => LabCategory::Hematologia,
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => LabResultType::Numeric,
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => [
                        ['analyte_id' => $analyte->id, 'value' => '14.5'],
                    ],
                ],
            ],
        ]
    );

    $response->assertStatus(409);
});

it('rejects store by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    $analyte = CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => LabCategory::Hematologia,
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => LabResultType::Numeric,
    ]);

    $response = $this->actingAs($doctorB)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => [
                        ['analyte_id' => $analyte->id, 'value' => '14.5'],
                    ],
                ],
            ],
        ]
    );

    $response->assertForbidden();
});

it('rejects store with empty panels and empty loose', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [],
            'loose' => [],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['panels']);
});

it('rejects future collection date', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => now()->addDay()->format('Y-m-d'),
            'loose' => [
                [
                    'name' => 'Glicose',
                    'value' => '92',
                    'unit' => 'mg/dL',
                    'reference_range' => '70-99',
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'loose' => [
                [
                    'name' => 'Glicose',
                    'value' => '92',
                    'unit' => 'mg/dL',
                ],
            ],
        ]
    );

    $response->assertUnauthorized();
});

it('extracts numeric value from comma-separated string', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    $analyte = CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => LabCategory::Hematologia,
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => LabResultType::Numeric,
    ]);

    $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => [
                        ['analyte_id' => $analyte->id, 'value' => '14,5'],
                    ],
                ],
            ],
        ]
    )->assertCreated();

    $this->assertDatabaseHas('valores_laboratoriais', [
        'prontuario_id' => $prontuario->id,
        'valor' => '14,5',
        'valor_numerico' => 14.5,
    ]);
});

it('rejects panel with non-existent analyte_id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/lab-results",
        [
            'date' => '2026-03-10',
            'panels' => [
                [
                    'panel_id' => $panel->id,
                    'panel_name' => $panel->nome,
                    'values' => [
                        ['analyte_id' => 'analito-inexistente', 'value' => '14.5'],
                    ],
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['panels.0.values.0.analyte_id']);
});
