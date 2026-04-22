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
     * Retrieve all attachments for a given medical record. Results are ordered by creation date (most recent first).
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 42
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 301,
     *       "medical_record_id": 42,
     *       "patient_id": 18,
     *       "attachment_type": "ecg",
     *       "name": "Laudo ECG — Maria Silva.pdf",
     *       "file_type": "pdf",
     *       "file_url": "http://localhost:8000/api/attachments/301/download?expires=1776272400&signature=f1a9c5b7e2d84a1f9c0e6b2d4a8c0f1e2b4c6d8a0e1f2a3b4c5d6e7f8a9b0c1d",
     *       "file_size": 248153,
     *       "processing_status": "completed",
     *       "extracted_data": {"date": "2026-04-22", "pattern": "normal"},
     *       "processing_error": null,
     *       "processed_at": "2026-04-22T14:05:12+00:00",
     *       "confirmed_at": null,
     *       "created_at": "2026-04-22T14:04:50+00:00",
     *       "updated_at": "2026-04-22T14:05:12+00:00"
     *     },
     *     {
     *       "id": 302,
     *       "medical_record_id": 42,
     *       "patient_id": 18,
     *       "attachment_type": "documento",
     *       "name": "Atestado.pdf",
     *       "file_type": "pdf",
     *       "file_url": "http://localhost:8000/api/attachments/302/download?expires=1776272400&signature=b7e2d84a1f9c0e6b2d4a8c0f1e2b4c6d8a0e1f2a3b4c5d6e7f8a9b0c1df1a9c5",
     *       "file_size": 95321,
     *       "processing_status": null,
     *       "extracted_data": null,
     *       "processing_error": null,
     *       "processed_at": null,
     *       "confirmed_at": null,
     *       "created_at": "2026-04-22T09:30:00+00:00",
     *       "updated_at": "2026-04-22T09:30:00+00:00"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Medical record not found" {"message": "Prontuário não encontrado."}
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
     * Upload a file (PDF or image) as an attachment for a medical record. The file is stored locally. Parseable types
     * (lab, ecg, rx, eco, etc.) are queued for AI parsing; `documento` and `outro` are stored as-is.
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 42
     *
     * @bodyParam tipo_anexo string required Attachment type. One of: lab, ecg, rx, eco, mapa, mrpa, dexa, teste_ergometrico, ecodoppler_carotidas, elastografia_hepatica, cat, cintilografia, pe_diabetico, holter, polissonografia, documento, outro. Example: ecg
     * @bodyParam file file required The file to upload. Accepted types: pdf, jpg, jpeg, png, gif. Max size: 10 MB.
     * @bodyParam nome string optional Custom display name. Defaults to the uploaded file's original name. Example: Laudo ECG — Maria Silva
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 303,
     *     "medical_record_id": 42,
     *     "patient_id": 18,
     *     "attachment_type": "ecg",
     *     "name": "Laudo ECG — Maria Silva.pdf",
     *     "file_type": "pdf",
     *     "file_url": "http://localhost:8000/api/attachments/303/download?expires=1776272400&signature=a1f9c5b7e2d84a1f9c0e6b2d4a8c0f1e2b4c6d8a0e1f2a3b4c5d6e7f8a9b0c1d",
     *     "file_size": 248153,
     *     "processing_status": "pending",
     *     "extracted_data": null,
     *     "processing_error": null,
     *     "processed_at": null,
     *     "confirmed_at": null,
     *     "created_at": "2026-04-22T14:04:50+00:00",
     *     "updated_at": "2026-04-22T14:04:50+00:00"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Medical record not found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Finalized medical record" {"message": "Não é possível anexar arquivos a um prontuário finalizado."}
     * @response 422 scenario="Validation error" {"message": "O arquivo deve ser PDF, JPG, JPEG, PNG ou GIF.", "errors": {"file": ["O arquivo deve ser PDF, JPG, JPEG, PNG ou GIF."], "tipo_anexo": ["O tipo de anexo informado é inválido."]}}
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
     * Return a single attachment with its current processing state.
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam id int required The attachment ID. Example: 301
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 301,
     *     "medical_record_id": 42,
     *     "patient_id": 18,
     *     "attachment_type": "ecg",
     *     "name": "Laudo ECG — Maria Silva.pdf",
     *     "file_type": "pdf",
     *     "file_url": "http://localhost:8000/api/attachments/301/download?expires=1776272400&signature=f1a9c5b7e2d84a1f9c0e6b2d4a8c0f1e2b4c6d8a0e1f2a3b4c5d6e7f8a9b0c1d",
     *     "file_size": 248153,
     *     "processing_status": "completed",
     *     "extracted_data": {"date": "2026-04-22", "pattern": "normal"},
     *     "processing_error": null,
     *     "processed_at": "2026-04-22T14:05:12+00:00",
     *     "confirmed_at": null,
     *     "created_at": "2026-04-22T14:04:50+00:00",
     *     "updated_at": "2026-04-22T14:05:12+00:00"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Not found" {"message": "Anexo não encontrado."}
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
     * Stream the underlying file as a download. Intended to be hit through a signed URL produced by the `file_url`
     * field on the attachment resource (signed for 30 minutes). Direct authenticated access is also supported for
     * same-origin clients.
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam id int required The attachment ID. Example: 301
     *
     * @response 200 scenario="File download" [Binary file content — Content-Type inferred from file_type, Content-Disposition: attachment; filename="Laudo ECG — Maria Silva.pdf"]
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Not found" {"message": "Anexo não encontrado."}
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
     * Resets a completed or failed attachment back to `pending` and re-queues the parse job. Not allowed for
     * `documento` or `outro` types.
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam id int required The attachment ID. Example: 301
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 301,
     *     "medical_record_id": 42,
     *     "patient_id": 18,
     *     "attachment_type": "ecg",
     *     "name": "Laudo ECG — Maria Silva.pdf",
     *     "file_type": "pdf",
     *     "file_url": "http://localhost:8000/api/attachments/301/download?expires=1776272400&signature=f1a9c5b7e2d84a1f9c0e6b2d4a8c0f1e2b4c6d8a0e1f2a3b4c5d6e7f8a9b0c1d",
     *     "file_size": 248153,
     *     "processing_status": "pending",
     *     "extracted_data": null,
     *     "processing_error": null,
     *     "processed_at": null,
     *     "confirmed_at": null,
     *     "created_at": "2026-04-22T14:04:50+00:00",
     *     "updated_at": "2026-04-22T14:20:00+00:00"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Not found" {"message": "Anexo não encontrado."}
     * @response 409 scenario="Not parseable" {"message": "Este tipo de anexo não é processável por IA."}
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
     * Persists the doctor-reviewed exam data extracted from the attachment. Replaces `extracted_data` with the posted
     * payload, transitions status to `confirmed`, stamps `confirmed_at`, and broadcasts `attachment.confirmed`. Only
     * allowed when the current status is `completed`.
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam id int required The attachment ID. Example: 304
     *
     * @bodyParam exam_data object required The doctor-validated exam data payload. The shape depends on the `attachment_type`. Example: {"date": "2026-04-22", "pattern": "normal"}
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 304,
     *     "medical_record_id": 42,
     *     "patient_id": 18,
     *     "attachment_type": "ecg",
     *     "name": "Laudo ECG — Maria Silva.pdf",
     *     "file_type": "pdf",
     *     "file_url": "http://localhost:8000/api/attachments/304/download?expires=1776272400&signature=e2d84a1f9c0e6b2d4a8c0f1e2b4c6d8a0e1f2a3b4c5d6e7f8a9b0c1df1a9c5b7",
     *     "file_size": 248153,
     *     "processing_status": "confirmed",
     *     "extracted_data": {"date": "2026-04-22", "pattern": "normal"},
     *     "processing_error": null,
     *     "processed_at": "2026-04-22T14:05:12+00:00",
     *     "confirmed_at": "2026-04-22T14:30:00+00:00",
     *     "created_at": "2026-04-22T14:04:50+00:00",
     *     "updated_at": "2026-04-22T14:30:00+00:00"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Not found" {"message": "Anexo não encontrado."}
     * @response 409 scenario="Not confirmable" {"message": "Somente anexos com processamento concluído podem ser confirmados."}
     * @response 422 scenario="Validation error" {"message": "Os dados do exame são obrigatórios.", "errors": {"exam_data": ["Os dados do exame são obrigatórios."]}}
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
     * Removes the attachment and its underlying file from storage. Forbidden for attachments already confirmed by the
     * doctor.
     *
     * @authenticated
     *
     * @group Attachments
     *
     * @urlParam id int required The attachment ID. Example: 301
     *
     * @response 204 scenario="Deleted" {}
     * @response 401 scenario="Unauthenticated" {"message": "Não autenticado."}
     * @response 403 scenario="Forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="Not found" {"message": "Anexo não encontrado."}
     * @response 409 scenario="Confirmed attachment" {"message": "Não é possível remover um anexo já confirmado."}
     */
    public function destroy(int $id): JsonResponse
    {
        $attachment = $this->attachmentService->findOrFail($id);
        Gate::authorize('delete', $attachment);

        $this->attachmentService->delete($id);

        return response()->json([], 204);
    }
}
