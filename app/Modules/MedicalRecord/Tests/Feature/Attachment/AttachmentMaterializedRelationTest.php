<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('eager-loads the anexo on a ResultadoEcg', function (): void {
    $prontuario = Prontuario::factory()->create();
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $ecg = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $fresh = ResultadoEcg::query()->with('anexo')->find($ecg->id);

    expect($fresh->anexo)->not->toBeNull()
        ->and($fresh->anexo->is($anexo))->toBeTrue();
});

it('loads the anexo on a ValorLaboratorial', function (): void {
    $prontuario = Prontuario::factory()->create();
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    $lab = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $fresh = ValorLaboratorial::query()->with('anexo')->find($lab->id);

    expect($fresh->anexo)->not->toBeNull()
        ->and($fresh->anexo->is($anexo))->toBeTrue();
});

it('resolves materializedResult for an Ecg anexo', function (): void {
    $prontuario = Prontuario::factory()->create();
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    $ecg = ResultadoEcg::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'anexo_id' => $anexo->id,
    ]);

    $relation = $anexo->materializedResult();
    expect($relation)->not->toBeNull();

    $resolved = $relation->first();
    expect($resolved)->not->toBeNull()
        ->and($resolved->is($ecg))->toBeTrue();
});

it('returns null materializedResult for documento, outro and lab', function (): void {
    $prontuario = Prontuario::factory()->create();

    foreach ([AttachmentType::Documento, AttachmentType::Outro, AttachmentType::Lab] as $type) {
        $anexo = Anexo::factory()->create([
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'tipo_anexo' => $type,
        ]);

        expect($anexo->materializedResult())->toBeNull();
    }
});

it('lists valoresLaboratoriais associated with a Lab anexo', function (): void {
    $prontuario = Prontuario::factory()->create();
    $anexo = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Lab,
    ]);

    ValorLaboratorial::factory()
        ->count(3)
        ->create([
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'anexo_id' => $anexo->id,
        ]);

    expect($anexo->valoresLaboratoriais()->count())->toBe(3);
});
