<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Events\AttachmentUploaded;
use App\Modules\MedicalRecord\Jobs\ParseAttachmentJob;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('uploads a pdf attachment for a draft medical record', function (): void {
    Storage::fake('anexos');
    Event::fake([AttachmentUploaded::class]);
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $file = UploadedFile::fake()->create('ecg.pdf', 500, 'application/pdf');

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Ecg->value,
            'file' => $file,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.attachment_type', AttachmentType::Ecg->value)
        ->assertJsonPath('data.processing_status', ProcessingStatus::Pending->value);

    $attachment = Anexo::query()->findOrFail($response->json('data.id'));
    Storage::disk('anexos')->assertExists($attachment->caminho);

    Event::assertDispatched(AttachmentUploaded::class);
    Queue::assertPushed(ParseAttachmentJob::class);
});

it('uploads a non-parseable documento without queuing parse job', function (): void {
    Storage::fake('anexos');
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $file = UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf');

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Documento->value,
            'file' => $file,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.attachment_type', AttachmentType::Documento->value)
        ->assertJsonPath('data.processing_status', null);

    Queue::assertNotPushed(ParseAttachmentJob::class);
});

it('uploads a jpg image for a parseable type', function (): void {
    Storage::fake('anexos');
    Queue::fake();

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $file = UploadedFile::fake()->image('x-ray.jpg');

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Rx->value,
            'file' => $file,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.attachment_type', AttachmentType::Rx->value)
        ->assertJsonPath('data.file_type', 'jpg');

    $attachment = Anexo::query()->findOrFail($response->json('data.id'));
    expect($attachment->caminho)->toEndWith('.jpg');
    Storage::disk('anexos')->assertExists($attachment->caminho);
});

it('rejects file with disallowed mime type', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $file = UploadedFile::fake()->create('video.mp4', 500, 'video/mp4');

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Ecg->value,
            'file' => $file,
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('rejects invalid tipo_anexo', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $file = UploadedFile::fake()->create('file.pdf', 200, 'application/pdf');

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => 'invalido',
            'file' => $file,
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['tipo_anexo']);
});

it('rejects request without file', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Ecg->value,
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('denies upload to a medical record owned by another doctor', function (): void {
    Storage::fake('anexos');

    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $file = UploadedFile::fake()->create('file.pdf', 200, 'application/pdf');

    $response = $this->actingAs($doctorB)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Ecg->value,
            'file' => $file,
        ]
    );

    $response->assertForbidden();
});

it('returns 401 when unauthenticated', function (): void {
    Storage::fake('anexos');

    $prontuario = Prontuario::factory()->create();

    $file = UploadedFile::fake()->create('file.pdf', 200, 'application/pdf');

    $response = $this->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Ecg->value,
            'file' => $file,
        ]
    );

    $response->assertUnauthorized();
});

it('rejects upload to a finalized medical record', function (): void {
    Storage::fake('anexos');

    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $file = UploadedFile::fake()->create('file.pdf', 200, 'application/pdf');

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/attachments",
        [
            'tipo_anexo' => AttachmentType::Ecg->value,
            'file' => $file,
        ]
    );

    $response->assertStatus(409);
});
