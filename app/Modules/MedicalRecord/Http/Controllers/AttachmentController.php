<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\ConfirmAttachmentDTO;
use App\Modules\MedicalRecord\DTOs\UploadAttachmentDTO;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Http\Requests\ConfirmAttachmentRequest;
use App\Modules\MedicalRecord\Http\Requests\UploadAttachmentRequest;
use App\Modules\MedicalRecord\Http\Resources\AttachmentResource;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    /**
     * List attachments of a medical record.
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function index(int $prontuarioId): AnonymousResourceCollection
    {
        $prontuario = $this->attachmentService->findMedicalRecordOrFail($prontuarioId);
        Gate::authorize('viewAnyForProntuario', [Anexo::class, $prontuario]);

        return AttachmentResource::collection(
            $this->attachmentService->listForProntuario($prontuarioId)
        );
    }

    /**
     * Upload a file as an attachment for a medical record.
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function store(UploadAttachmentRequest $request, int $prontuarioId): JsonResponse
    {
        $prontuario = $this->attachmentService->findMedicalRecordOrFail($prontuarioId);
        Gate::authorize('create', [Anexo::class, $prontuario]);

        $dto = new UploadAttachmentDTO(
            prontuarioId: $prontuarioId,
            tipoAnexo: AttachmentType::from($request->validated('tipo_anexo')),
            file: $request->file('file'),
            nome: $request->validated('nome'),
        );

        $attachment = $this->attachmentService->upload($dto);

        return (new AttachmentResource($attachment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single attachment with its current processing state.
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function show(int $id): AttachmentResource
    {
        $attachment = $this->attachmentService->findOrFail($id);
        Gate::authorize('view', $attachment);

        return new AttachmentResource($attachment);
    }

    /**
     * Download the raw file.
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function download(int $id): StreamedResponse
    {
        $attachment = $this->attachmentService->findOrFail($id);
        Gate::authorize('view', $attachment);

        return Storage::disk('anexos')->download(
            $attachment->caminho,
            $attachment->nome,
        );
    }

    /**
     * Retry AI parsing for a failed or completed attachment.
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function retry(int $id): AttachmentResource
    {
        $attachment = $this->attachmentService->findOrFail($id);
        Gate::authorize('update', $attachment);

        return new AttachmentResource($this->attachmentService->retryParse($id));
    }

    /**
     * Confirm the doctor-reviewed extracted data for an attachment.
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function confirm(ConfirmAttachmentRequest $request, int $id): AttachmentResource
    {
        $attachment = $this->attachmentService->findOrFail($id);
        Gate::authorize('update', $attachment);

        $dto = new ConfirmAttachmentDTO(
            attachmentId: $id,
            examData: $request->validated('exam_data'),
        );

        return new AttachmentResource($this->attachmentService->confirm($dto));
    }

    /**
     * Delete an attachment (not allowed after confirmation).
     *
     * @authenticated
     *
     * @group Attachments
     */
    public function destroy(int $id): JsonResponse
    {
        $attachment = $this->attachmentService->findOrFail($id);
        Gate::authorize('delete', $attachment);

        $this->attachmentService->delete($id);

        return response()->json([], 204);
    }
}
