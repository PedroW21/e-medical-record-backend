<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('downloads the underlying file with the original filename', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $path = 'anexos/'.$prontuario->id.'/'.Str::uuid()->toString().'.pdf';

    Storage::disk('anexos')->put($path, 'fake pdf content');

    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'caminho' => $path,
        'nome' => 'relatorio-ecg.pdf',
    ]);

    $response = $this->actingAs($doctor)->get("/api/attachments/{$attachment->id}/download");

    $response->assertOk();
    $response->assertDownload('relatorio-ecg.pdf');
});

it('denies download to non-owner doctor', function (): void {
    Storage::fake('anexos');

    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $path = 'anexos/'.$prontuario->id.'/file.pdf';
    Storage::disk('anexos')->put($path, 'fake');

    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'caminho' => $path,
    ]);

    $response = $this->actingAs($doctorB)->get("/api/attachments/{$attachment->id}/download");

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    Storage::fake('anexos');

    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->getJson("/api/attachments/{$attachment->id}/download");

    $response->assertUnauthorized();
});
