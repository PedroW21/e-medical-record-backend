<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;

// ─── ECG ─────────────────────────────────────────────────────────────────────

it('stores an ECG result with normal pattern', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertCreated()
        ->assertJsonPath('data.date', '2026-03-15')
        ->assertJsonPath('data.pattern', 'normal')
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.patient_id', $prontuario->paciente_id);

    $this->assertDatabaseHas('resultados_ecg', [
        'prontuario_id' => $prontuario->id,
        'padrao' => 'normal',
    ]);
});

it('stores an ECG result with altered pattern and custom text', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        [
            'date' => '2026-03-15',
            'pattern' => 'altered',
            'custom_text' => 'Sobrecarga ventricular esquerda com alterações inespecíficas de repolarização.',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.pattern', 'altered')
        ->assertJsonPath('data.custom_text', 'Sobrecarga ventricular esquerda com alterações inespecíficas de repolarização.');

    $this->assertDatabaseHas('resultados_ecg', [
        'prontuario_id' => $prontuario->id,
        'padrao' => 'altered',
        'texto_personalizado' => 'Sobrecarga ventricular esquerda com alterações inespecíficas de repolarização.',
    ]);
});

// ─── X-ray ───────────────────────────────────────────────────────────────────

it('stores an X-ray result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/xray",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertCreated()
        ->assertJsonPath('data.pattern', 'normal');

    $this->assertDatabaseHas('resultados_rx', [
        'prontuario_id' => $prontuario->id,
        'padrao' => 'normal',
    ]);
});

// ─── Free Text ───────────────────────────────────────────────────────────────

it('stores a free text result for holter', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/free-text",
        [
            'date' => '2026-03-15',
            'type' => 'holter',
            'text' => 'Ritmo sinusal. Sem eventos arrítmicos significativos em 24 horas.',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.type', 'holter')
        ->assertJsonPath('data.text', 'Ritmo sinusal. Sem eventos arrítmicos significativos em 24 horas.');

    $this->assertDatabaseHas('resultados_texto_livre', [
        'prontuario_id' => $prontuario->id,
        'tipo' => 'holter',
        'texto' => 'Ritmo sinusal. Sem eventos arrítmicos significativos em 24 horas.',
    ]);
});

// ─── Temperature ─────────────────────────────────────────────────────────────

it('stores a temperature record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/temperature",
        ['date' => '2026-03-15', 'time' => '08:30', 'value' => 36.5]
    );

    $response->assertCreated()
        ->assertJsonPath('data.time', '08:30')
        ->assertJsonPath('data.value', '36.5');

    $this->assertDatabaseHas('registros_temperatura', [
        'prontuario_id' => $prontuario->id,
        'hora' => '08:30',
        'valor' => 36.5,
    ]);
});

// ─── Hepatic Elastography ────────────────────────────────────────────────────

it('stores a hepatic elastography result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/hepatic-elastography",
        [
            'date' => '2026-03-15',
            'fat_fraction' => 5.2,
            'tsi' => 250.0,
            'kpa' => 6.1,
            'observations' => 'Fibrose hepática leve (F1).',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.fat_fraction', '5.20')
        ->assertJsonPath('data.kpa', '6.10');

    $this->assertDatabaseHas('resultados_elastografia_hepatica', [
        'prontuario_id' => $prontuario->id,
        'fracao_gordura' => 5.2,
        'kpa' => 6.1,
    ]);
});

// ─── MAPA ────────────────────────────────────────────────────────────────────

it('stores a MAPA result with BP values and overrides', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mapa",
        [
            'date' => '2026-03-15',
            'systolic_awake' => 128.5,
            'diastolic_awake' => 82.3,
            'systolic_sleep' => 110.2,
            'diastolic_sleep' => 68.7,
            'systolic_24h' => 120.4,
            'diastolic_24h' => 76.5,
            'systolic_24h_override' => false,
            'diastolic_24h_override' => false,
            'nocturnal_dipping_systolic' => 14.2,
            'nocturnal_dipping_systolic_override' => false,
            'nocturnal_dipping_diastolic' => 16.8,
            'nocturnal_dipping_diastolic_override' => false,
            'notes' => 'Descenso noturno adequado.',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.systolic_awake', '128.50')
        ->assertJsonPath('data.diastolic_awake', '82.30')
        ->assertJsonPath('data.nocturnal_dipping_systolic', '14.20');

    $this->assertDatabaseHas('resultados_mapa', [
        'prontuario_id' => $prontuario->id,
        'pas_vigilia' => 128.5,
        'pad_vigilia' => 82.3,
        'observacoes' => 'Descenso noturno adequado.',
    ]);
});

// ─── DEXA ────────────────────────────────────────────────────────────────────

it('stores a DEXA result with body composition data', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/dexa",
        [
            'date' => '2026-03-15',
            'total_weight' => 82.5,
            'bmd' => 1.24,
            't_score' => -0.8,
            'body_fat_pct' => 28.3,
            'total_fat' => 23.3,
            'bmi' => 27.4,
            'lean_mass' => 57.2,
            'lean_mass_pct' => 69.3,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.total_weight', '82.50')
        ->assertJsonPath('data.body_fat_pct', '28.30')
        ->assertJsonPath('data.t_score', '-0.80');

    $this->assertDatabaseHas('resultados_dexa', [
        'prontuario_id' => $prontuario->id,
        'peso_total' => 82.5,
        'gordura_corporal_pct' => 28.3,
    ]);
});

// ─── Ergometric Test ─────────────────────────────────────────────────────────

it('stores an ergometric test result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ergometric-test",
        [
            'date' => '2026-03-15',
            'protocol' => 'bruce',
            'hr_max_predicted_pct' => 92.5,
            'hr_max' => 168,
            'bp_systolic_max' => 195,
            'bp_systolic_pre' => 130,
            'met_max' => 10.2,
            'cardio_respiratory_fitness' => 'excellent',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.protocol', 'bruce')
        ->assertJsonPath('data.hr_max', 168)
        ->assertJsonPath('data.cardio_respiratory_fitness', 'excellent');

    $this->assertDatabaseHas('resultados_teste_ergometrico', [
        'prontuario_id' => $prontuario->id,
        'protocolo' => 'bruce',
        'fc_max' => 168,
    ]);
});

// ─── Carotid Ecodoppler ──────────────────────────────────────────────────────

it('stores a carotid ecodoppler with bilateral measurements', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/carotid-ecodoppler",
        [
            'date' => '2026-03-15',
            'common_carotid_left' => ['intimal_thickness' => 0.80, 'stenosis_degree' => 20.0],
            'common_carotid_right' => ['intimal_thickness' => 0.75, 'stenosis_degree' => 15.0],
            'vertebral_left' => ['intimal_thickness' => 0.60, 'stenosis_degree' => 0.0],
            'vertebral_right' => ['intimal_thickness' => 0.58, 'stenosis_degree' => 0.0],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.common_carotid_left.intimal_thickness', 0.8)
        ->assertJsonPath('data.common_carotid_left.stenosis_degree', 20)
        ->assertJsonPath('data.common_carotid_right.intimal_thickness', 0.75);

    $this->assertDatabaseHas('resultados_ecodoppler_carotidas', [
        'prontuario_id' => $prontuario->id,
        'espessura_intimal_carotida_comum_e' => 0.80,
        'grau_estenose_carotida_comum_e' => 20.0,
        'espessura_intimal_carotida_comum_d' => 0.75,
    ]);
});

it('stores a carotid ecodoppler with bulb_internal fields correctly mapped', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/carotid-ecodoppler",
        [
            'date' => '2026-03-15',
            'bulb_internal_left' => [
                'intimal_thickness' => 0.85,
                'stenosis_degree' => 25.0,
            ],
            'bulb_internal_right' => [
                'intimal_thickness' => 0.90,
                'stenosis_degree' => 30.0,
            ],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.bulb_internal_left.intimal_thickness', 0.85)
        ->assertJsonPath('data.bulb_internal_left.stenosis_degree', 25)
        ->assertJsonPath('data.bulb_internal_right.intimal_thickness', 0.90)
        ->assertJsonPath('data.bulb_internal_right.stenosis_degree', 30);

    $this->assertDatabaseHas('resultados_ecodoppler_carotidas', [
        'prontuario_id' => $prontuario->id,
        'espessura_intimal_bulbo_interna_e' => 0.85,
        'grau_estenose_bulbo_interna_e' => 25.0,
        'espessura_intimal_bulbo_interna_d' => 0.90,
        'grau_estenose_bulbo_interna_d' => 30.0,
    ]);
});

// ─── Echo ────────────────────────────────────────────────────────────────────

it('stores an echo result with valve assessments', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/echo",
        [
            'date' => '2026-03-15',
            'type' => 'transthoracic',
            'ef' => 62.5,
            'la_mm' => 38.0,
            'septum' => 10.0,
            'lvedd' => 50.0,
            'lvesd' => 32.0,
            'valve_aortic' => ['status' => 'alterada', 'description' => 'Estenose leve com área valvar estimada de 1,8 cm².'],
            'valve_mitral' => ['status' => 'regular'],
            'valve_tricuspid' => ['status' => 'regular'],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.type', 'transthoracic')
        ->assertJsonPath('data.ef', '62.50')
        ->assertJsonPath('data.valve_aortic.status', 'alterada')
        ->assertJsonPath('data.valve_aortic.description', 'Estenose leve com área valvar estimada de 1,8 cm².');

    $this->assertDatabaseHas('resultados_ecocardiograma', [
        'prontuario_id' => $prontuario->id,
        'tipo' => 'transthoracic',
        'fe' => 62.5,
    ]);
});

// ─── MRPA ────────────────────────────────────────────────────────────────────

it('stores an MRPA result with measurements', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/mrpa",
        [
            'date' => '2026-03-15',
            'days_monitored' => 7,
            'limb' => 'right_arm',
            'measurements' => [
                ['date' => '2026-03-08', 'time' => '07:00', 'period' => 'morning', 'systolic' => 128, 'diastolic' => 82],
                ['date' => '2026-03-08', 'time' => '21:00', 'period' => 'evening', 'systolic' => 122, 'diastolic' => 78],
                ['date' => '2026-03-09', 'time' => '07:15', 'period' => 'morning', 'systolic' => 130, 'diastolic' => 84],
            ],
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.days_monitored', 7)
        ->assertJsonPath('data.limb', 'right_arm')
        ->assertJsonCount(3, 'data.measurements');

    $mrpa = ResultadoMrpa::query()->where('prontuario_id', $prontuario->id)->firstOrFail();

    $this->assertDatabaseHas('resultados_mrpa', [
        'prontuario_id' => $prontuario->id,
        'dias_monitorados' => 7,
        'membro' => 'right_arm',
    ]);

    $this->assertDatabaseCount('medicoes_mrpa', 3);
    $this->assertDatabaseHas('medicoes_mrpa', [
        'resultado_mrpa_id' => $mrpa->id,
        'periodo' => 'morning',
        'pas' => 128,
        'pad' => 82,
    ]);
});

// ─── CAT ─────────────────────────────────────────────────────────────────────

it('stores a CAT result with artery data', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/cat",
        [
            'date' => '2026-03-15',
            'cd' => ['status' => 'pervia'],
            'ce' => ['status' => 'pervia'],
            'da' => ['status' => 'obstrucao', 'proximal' => ['has_obstruction' => true, 'percentage' => 70]],
            'cx' => ['status' => 'pervia'],
            'stents' => [
                ['artery' => 'da', 'status' => 'pervio'],
            ],
            'observations' => 'Obstrução proximal em DA com implante de stent.',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.cd.status', 'pervia')
        ->assertJsonPath('data.da.status', 'obstrucao')
        ->assertJsonPath('data.stents.0.artery', 'da');

    $this->assertDatabaseHas('resultados_cat', [
        'prontuario_id' => $prontuario->id,
        'observacoes' => 'Obstrução proximal em DA com implante de stent.',
    ]);
});

// ─── Scintigraphy ────────────────────────────────────────────────────────────

it('stores a scintigraphy result with perfusion territories', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/scintigraphy",
        [
            'date' => '2026-03-15',
            'protocol' => 'one_day_stress_rest',
            'stress_modality' => 'physical',
            'hr_max' => 155,
            'hr_max_predicted_pct' => 88.0,
            'perfusion_da' => ['stress' => 'normal', 'rest' => 'normal', 'reversibility' => null],
            'perfusion_cx' => ['stress' => 'normal', 'rest' => 'normal', 'reversibility' => null],
            'perfusion_cd' => ['stress' => 'mild_hypoperfusion', 'rest' => 'normal', 'reversibility' => 'reversible'],
            'sss' => 3,
            'srs' => 0,
            'sds' => 3,
            'sds_classification' => 'normal',
            'global_result' => 'normal',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.protocol', 'one_day_stress_rest')
        ->assertJsonPath('data.perfusion_da.stress', 'normal')
        ->assertJsonPath('data.perfusion_cd.stress', 'mild_hypoperfusion')
        ->assertJsonPath('data.sss', 3);

    $this->assertDatabaseHas('resultados_cintilografia', [
        'prontuario_id' => $prontuario->id,
        'perfusao_da_estresse' => 'normal',
        'perfusao_cd_estresse' => 'mild_hypoperfusion',
        'sss' => 3,
    ]);
});

// ─── Diabetic Foot ───────────────────────────────────────────────────────────

it('stores a diabetic foot screening with sections and scores', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/diabetic-foot",
        [
            'date' => '2026-03-15',
            'nss_score' => 4,
            'nds_score' => 6,
            'nds_override' => false,
            'itb_right' => 0.92,
            'itb_left' => 0.88,
            'itb_right_override' => false,
            'itb_left_override' => false,
            'iwgdf_category' => '2',
            'iwgdf_category_override' => false,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.nss_score', 4)
        ->assertJsonPath('data.nds_score', 6)
        ->assertJsonPath('data.itb_right', '0.9200')
        ->assertJsonPath('data.iwgdf_category', '2');

    $this->assertDatabaseHas('resultados_pe_diabetico', [
        'prontuario_id' => $prontuario->id,
        'nss_score' => 4,
        'nds_score' => 6,
        'itb_direito' => 0.92,
        'categoria_iwgdf' => '2',
    ]);
});

// ─── Authorization & Validation ──────────────────────────────────────────────

it('rejects store by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertForbidden();
    $this->assertDatabaseEmpty('resultados_ecg');
});

it('rejects store on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertStatus(409);
    $this->assertDatabaseEmpty('resultados_ecg');
});

it('rejects unauthenticated store', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertUnauthorized();
});

it('rejects invalid exam type slug', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/tipo-invalido",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertNotFound();
});

it('validates required fields for ECG', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15']
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['pattern']);
});

it('rejects future dates', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => now()->addDay()->format('Y-m-d'), 'pattern' => 'normal']
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

it('rejects store on non-existent medical record', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson(
        '/api/medical-records/99999/exam-results/ecg',
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertNotFound();
});
