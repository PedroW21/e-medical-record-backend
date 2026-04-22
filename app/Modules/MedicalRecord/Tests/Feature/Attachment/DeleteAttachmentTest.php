<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Storage;

it('deletes a pending attachment and removes file from disk', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $path = 'anexos/'.$prontuario->id.'/file.pdf';
    Storage::disk('anexos')->put($path, 'fake content');

    $attachment = Anexo::factory()->parseable()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'caminho' => $path,
    ]);

    $response = $this->actingAs($doctor)->deleteJson("/api/attachments/{$attachment->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('anexos', ['id' => $attachment->id]);
    Storage::disk('anexos')->assertMissing($path);
});

it('deletes a completed attachment', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->completed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson("/api/attachments/{$attachment->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('anexos', ['id' => $attachment->id]);
});

it('deletes a failed attachment', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->failed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson("/api/attachments/{$attachment->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('anexos', ['id' => $attachment->id]);
});

it('forbids deletion of a confirmed attachment', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $attachment = Anexo::factory()->confirmed()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson("/api/attachments/{$attachment->id}");

    $response->assertStatus(409);
    $this->assertDatabaseHas('anexos', ['id' => $attachment->id]);
});

it('denies deletion to non-owner doctor', function (): void {
    Storage::fake('anexos');

    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctorB)->deleteJson("/api/attachments/{$attachment->id}");

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    $prontuario = Prontuario::factory()->create();
    $attachment = Anexo::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->deleteJson("/api/attachments/{$attachment->id}");

    $response->assertUnauthorized();
});
