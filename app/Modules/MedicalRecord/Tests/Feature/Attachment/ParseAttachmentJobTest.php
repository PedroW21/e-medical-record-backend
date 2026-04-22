<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Events\AttachmentParseCompleted;
use App\Modules\MedicalRecord\Jobs\ParseAttachmentJob;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Event;

it('marks a parseable attachment as completed with mock extracted data', function (): void {
    Event::fake([AttachmentParseCompleted::class]);

    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->parseable()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Ecg,
    ]);

    (new ParseAttachmentJob($attachment->id))->handle();

    $fresh = $attachment->fresh();
    expect($fresh->status_processamento)->toBe(ProcessingStatus::Completed);
    expect($fresh->dados_extraidos)->toMatchArray([
        'pattern' => 'normal',
    ]);
    expect($fresh->dados_extraidos['date'])->toBe(now()->toDateString());
    expect($fresh->processado_em)->not->toBeNull();

    Event::assertDispatched(AttachmentParseCompleted::class);
});

it('skips documento attachments (no-op)', function (): void {
    Event::fake();

    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Documento,
        'status_processamento' => null,
    ]);

    (new ParseAttachmentJob($attachment->id))->handle();

    $fresh = $attachment->fresh();
    expect($fresh->status_processamento)->toBeNull();
    expect($fresh->dados_extraidos)->toBeNull();

    Event::assertNotDispatched(AttachmentParseCompleted::class);
});

it('skips outro attachments (no-op)', function (): void {
    Event::fake();

    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => AttachmentType::Outro,
        'status_processamento' => null,
    ]);

    (new ParseAttachmentJob($attachment->id))->handle();

    $fresh = $attachment->fresh();
    expect($fresh->status_processamento)->toBeNull();
    expect($fresh->dados_extraidos)->toBeNull();

    Event::assertNotDispatched(AttachmentParseCompleted::class);
});

it('returns without error when attachment does not exist', function (): void {
    Event::fake();

    (new ParseAttachmentJob(99999))->handle();

    expect(Anexo::query()->find(99999))->toBeNull();
    Event::assertNotDispatched(AttachmentParseCompleted::class);
});

it('produces the correct mock shape per parseable type', function (AttachmentType $type, array $expectedKeys): void {
    Event::fake([AttachmentParseCompleted::class]);

    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->parseable()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'tipo_anexo' => $type,
    ]);

    (new ParseAttachmentJob($attachment->id))->handle();

    $fresh = $attachment->fresh();
    expect($fresh->status_processamento)->toBe(ProcessingStatus::Completed);

    foreach ($expectedKeys as $key) {
        expect($fresh->dados_extraidos)->toHaveKey($key);
    }

    Event::assertDispatched(AttachmentParseCompleted::class);
})->with([
    'ecg' => [AttachmentType::Ecg, ['date', 'pattern']],
    'rx' => [AttachmentType::Rx, ['date', 'pattern']],
    'eco' => [AttachmentType::Eco, ['date', 'type', 'ef']],
    'lab' => [AttachmentType::Lab, ['date', 'panels', 'loose']],
    'mapa' => [AttachmentType::Mapa, ['date', 'systolic_awake', 'diastolic_awake']],
    'holter' => [AttachmentType::Holter, ['date', 'text']],
]);
