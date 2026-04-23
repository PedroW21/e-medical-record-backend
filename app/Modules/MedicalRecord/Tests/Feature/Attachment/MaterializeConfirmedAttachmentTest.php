<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\LabCategory;
use App\Modules\MedicalRecord\Enums\LabResultType;
use App\Modules\MedicalRecord\Events\AttachmentConfirmed;
use App\Modules\MedicalRecord\Listeners\MaterializeConfirmedAttachment;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ResultadoEcocardiograma;
use App\Modules\MedicalRecord\Models\ResultadoTextoLivre;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

/**
 * Helper: build a confirmed anexo in the given type with `dados_extraidos` set.
 *
 * @param  array<string, mixed>|null  $extractedData
 */
function makeConfirmedAnexo(AttachmentType $type, ?array $extractedData): Anexo
{
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    return Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => $type,
        'dados_extraidos' => $extractedData,
    ]);
}

function fireListener(Anexo $anexo): void
{
    app(MaterializeConfirmedAttachment::class)
        ->handle(new AttachmentConfirmed($anexo->fresh()));
}

// ─── ECG ─────────────────────────────────────────────────────────────────────

it('creates an ECG exam result row when an ECG attachment is confirmed', function (): void {
    $payload = ['date' => '2026-04-22', 'pattern' => 'normal'];
    $anexo = makeConfirmedAnexo(AttachmentType::Ecg, $payload);

    fireListener($anexo);

    $this->assertDatabaseCount('resultados_ecg', 1);
    $this->assertDatabaseHas('resultados_ecg', [
        'prontuario_id' => $anexo->prontuario_id,
        'paciente_id' => $anexo->paciente_id,
        'anexo_id' => $anexo->id,
        'padrao' => 'normal',
    ]);

    $row = ResultadoEcg::query()->where('anexo_id', $anexo->id)->firstOrFail();
    expect($row->data->toDateString())->toBe('2026-04-22');
});

// ─── Echo ────────────────────────────────────────────────────────────────────

it('creates an echo result for eco attachment', function (): void {
    $payload = ['date' => '2026-04-22', 'type' => 'transthoracic', 'ef' => 60];
    $anexo = makeConfirmedAnexo(AttachmentType::Eco, $payload);

    fireListener($anexo);

    $this->assertDatabaseCount('resultados_ecocardiograma', 1);
    $this->assertDatabaseHas('resultados_ecocardiograma', [
        'prontuario_id' => $anexo->prontuario_id,
        'anexo_id' => $anexo->id,
        'tipo' => 'transthoracic',
        'fe' => 60,
    ]);

    $row = ResultadoEcocardiograma::query()->where('anexo_id', $anexo->id)->firstOrFail();
    expect($row->data->toDateString())->toBe('2026-04-22');
});

// ─── Free Text ───────────────────────────────────────────────────────────────

it('creates a free-text result for holter with proper tipo discriminator', function (): void {
    $payload = [
        'date' => '2026-04-22',
        'type' => 'holter',
        'text' => 'Holter 24h com ritmo sinusal predominante e raras extrassístoles ventriculares.',
    ];
    $anexo = makeConfirmedAnexo(AttachmentType::Holter, $payload);

    fireListener($anexo);

    $this->assertDatabaseCount('resultados_texto_livre', 1);
    $this->assertDatabaseHas('resultados_texto_livre', [
        'prontuario_id' => $anexo->prontuario_id,
        'anexo_id' => $anexo->id,
        'tipo' => 'holter',
        'texto' => 'Holter 24h com ritmo sinusal predominante e raras extrassístoles ventriculares.',
    ]);
});

it('creates a free-text result for polissonografia', function (): void {
    $payload = [
        'date' => '2026-04-22',
        'type' => 'polysomnography',
        'text' => 'Polissonografia basal. IAH de 12 eventos/hora — apneia leve.',
    ];
    $anexo = makeConfirmedAnexo(AttachmentType::Polissonografia, $payload);

    fireListener($anexo);

    $this->assertDatabaseCount('resultados_texto_livre', 1);
    $this->assertDatabaseHas('resultados_texto_livre', [
        'prontuario_id' => $anexo->prontuario_id,
        'anexo_id' => $anexo->id,
        'tipo' => 'polysomnography',
        'texto' => 'Polissonografia basal. IAH de 12 eventos/hora — apneia leve.',
    ]);
});

// ─── Lab ─────────────────────────────────────────────────────────────────────

it('creates N ValorLaboratorial rows for a lab attachment with panels', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    collect(['hemo-hemoglobina', 'hemo-hematocrito', 'hemo-leucocitos'])
        ->each(fn (string $id) => CatalogoExameLaboratorial::query()->create([
            'id' => $id,
            'nome' => ucfirst(str_replace('-', ' ', $id)),
            'categoria' => LabCategory::Hematologia,
            'unidade' => 'g/dL',
            'faixa_referencia' => '13,5-17,5',
            'tipo_resultado' => LabResultType::Numeric,
        ]));

    $payload = [
        'date' => '2026-04-22',
        'panels' => [
            [
                'panel_id' => $panel->id,
                'panel_name' => $panel->nome,
                'is_custom' => false,
                'values' => [
                    ['analyte_id' => 'hemo-hemoglobina', 'value' => '14.5'],
                    ['analyte_id' => 'hemo-hematocrito', 'value' => '42.3'],
                    ['analyte_id' => 'hemo-leucocitos', 'value' => '7200'],
                ],
            ],
        ],
    ];

    $anexo = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
        'dados_extraidos' => $payload,
    ]);

    fireListener($anexo);

    expect(ValorLaboratorial::query()
        ->where('prontuario_id', $prontuario->id)
        ->where('anexo_id', $anexo->id)
        ->count())->toBe(3);

    $this->assertDatabaseHas('valores_laboratoriais', [
        'anexo_id' => $anexo->id,
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'valor' => '14.5',
    ]);
});

// ─── No-op cases ─────────────────────────────────────────────────────────────

it('is a no-op for documento attachments', function (): void {
    $anexo = makeConfirmedAnexo(AttachmentType::Documento, ['note' => 'qualquer coisa']);

    fireListener($anexo);

    expect(ResultadoEcg::query()->count())->toBe(0);
    expect(ResultadoTextoLivre::query()->count())->toBe(0);
    expect(ValorLaboratorial::query()->count())->toBe(0);
});

it('is a no-op for outro attachments', function (): void {
    $anexo = makeConfirmedAnexo(AttachmentType::Outro, ['note' => 'qualquer coisa']);

    fireListener($anexo);

    expect(ResultadoEcg::query()->count())->toBe(0);
    expect(ResultadoTextoLivre::query()->count())->toBe(0);
    expect(ValorLaboratorial::query()->count())->toBe(0);
});

it('is a no-op when dados_extraidos is empty or null', function (): void {
    $anexoNull = makeConfirmedAnexo(AttachmentType::Ecg, null);
    fireListener($anexoNull);

    expect(ResultadoEcg::query()->count())->toBe(0);

    $anexoEmpty = makeConfirmedAnexo(AttachmentType::Ecg, []);
    fireListener($anexoEmpty);

    expect(ResultadoEcg::query()->count())->toBe(0);
});

// ─── Idempotency ─────────────────────────────────────────────────────────────

it('is idempotent — re-confirming updates the existing exam row instead of creating a duplicate', function (): void {
    $payload = ['date' => '2026-04-22', 'pattern' => 'normal'];
    $anexo = makeConfirmedAnexo(AttachmentType::Ecg, $payload);

    fireListener($anexo);

    expect(ResultadoEcg::query()->where('anexo_id', $anexo->id)->count())->toBe(1);

    $anexo->update(['dados_extraidos' => [
        'date' => '2026-04-23',
        'pattern' => 'altered',
        'custom_text' => 'Sobrecarga ventricular esquerda.',
    ]]);

    fireListener($anexo);

    expect(ResultadoEcg::query()->where('anexo_id', $anexo->id)->count())->toBe(1);

    $this->assertDatabaseHas('resultados_ecg', [
        'anexo_id' => $anexo->id,
        'padrao' => 'altered',
        'texto_personalizado' => 'Sobrecarga ventricular esquerda.',
    ]);

    $row = ResultadoEcg::query()->where('anexo_id', $anexo->id)->firstOrFail();
    expect($row->data->toDateString())->toBe('2026-04-23');
});

it('replaces all lab analyte rows when re-confirming the same lab anexo', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $panel = PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => LabCategory::Hematologia,
        'subsecoes' => [],
    ]);

    collect(['hemo-hemoglobina', 'hemo-hematocrito', 'hemo-leucocitos', 'hemo-plaquetas', 'hemo-eritrocitos'])
        ->each(fn (string $id) => CatalogoExameLaboratorial::query()->create([
            'id' => $id,
            'nome' => ucfirst(str_replace('-', ' ', $id)),
            'categoria' => LabCategory::Hematologia,
            'unidade' => 'g/dL',
            'faixa_referencia' => '10-20',
            'tipo_resultado' => LabResultType::Numeric,
        ]));

    $firstPayload = [
        'date' => '2026-04-22',
        'panels' => [
            [
                'panel_id' => $panel->id,
                'panel_name' => $panel->nome,
                'values' => [
                    ['analyte_id' => 'hemo-hemoglobina', 'value' => '14.5'],
                    ['analyte_id' => 'hemo-hematocrito', 'value' => '42.3'],
                    ['analyte_id' => 'hemo-leucocitos', 'value' => '7200'],
                ],
            ],
        ],
    ];

    $anexo = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
        'dados_extraidos' => $firstPayload,
    ]);

    fireListener($anexo);

    expect(ValorLaboratorial::query()->where('anexo_id', $anexo->id)->count())->toBe(3);

    $secondPayload = [
        'date' => '2026-04-23',
        'panels' => [
            [
                'panel_id' => $panel->id,
                'panel_name' => $panel->nome,
                'values' => [
                    ['analyte_id' => 'hemo-plaquetas', 'value' => '250000'],
                    ['analyte_id' => 'hemo-eritrocitos', 'value' => '5.1'],
                ],
            ],
        ],
    ];

    $anexo->update(['dados_extraidos' => $secondPayload]);

    fireListener($anexo);

    $rows = ValorLaboratorial::query()->where('anexo_id', $anexo->id)->get();

    expect($rows)->toHaveCount(2);
    expect($rows->pluck('catalogo_exame_id')->sort()->values()->all())
        ->toBe(['hemo-eritrocitos', 'hemo-plaquetas']);
});

// ─── Listener registration ───────────────────────────────────────────────────

it('is a queued listener registered on AttachmentConfirmed', function (): void {
    /** @var array<int, callable|string> $listeners */
    $listeners = \Illuminate\Support\Facades\Event::getListeners(AttachmentConfirmed::class);

    expect($listeners)->not->toBeEmpty();

    $found = collect($listeners)->contains(function (mixed $listener): bool {
        if (! is_object($listener)) {
            return false;
        }

        $reflection = new ReflectionFunction(\Closure::fromCallable($listener));
        $payload = $reflection->getStaticVariables();

        $target = $payload['listener'] ?? null;
        $class = $payload['class'] ?? null;

        return $target === MaterializeConfirmedAttachment::class
            || $class === MaterializeConfirmedAttachment::class
            || (is_array($target) && ($target[0] ?? null) === MaterializeConfirmedAttachment::class)
            || (is_string($target) && str_contains($target, 'MaterializeConfirmedAttachment'));
    });

    expect($found)->toBeTrue();
});
