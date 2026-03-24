<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreateExamRequestDTO;
use App\Modules\MedicalRecord\DTOs\UpdateExamRequestDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreExamRequestRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateExamRequestRequest;
use App\Modules\MedicalRecord\Http\Resources\ExamRequestResource;
use App\Modules\MedicalRecord\Services\ExamRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ExamRequestController
{
    public function __construct(
        private readonly ExamRequestService $examRequestService,
    ) {}

    /**
     * List all exam requests for a medical record.
     *
     * @authenticated
     *
     * @group Exam Requests
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "medical_record_id": 1,
     *       "model_id": null,
     *       "cid_10": "E11.9",
     *       "clinical_indication": "Acompanhamento de diabetes mellitus tipo 2.",
     *       "items": [{"id": "hemograma", "name": "Hemograma completo", "tuss_code": "40302566", "selected": true}],
     *       "medical_report": null,
     *       "printed_at": null,
     *       "created_at": "2026-03-10T10:00:00.000000Z",
     *       "updated_at": "2026-03-10T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     */
    public function index(Request $request, int $medicalRecordId): AnonymousResourceCollection
    {
        $prontuario = $this->examRequestService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('view', $prontuario);

        $examRequests = $this->examRequestService->listForRecord($medicalRecordId);

        return ExamRequestResource::collection($examRequests);
    }

    /**
     * Create a new exam request for a medical record.
     *
     * @authenticated
     *
     * @group Exam Requests
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @bodyParam items array required List of exam items (min 1). Example: [{"id":"hemograma","name":"Hemograma completo","tuss_code":"40302566","selected":true}]
     * @bodyParam model_id string nullable The model ID used to generate this request. Example: null
     * @bodyParam cid_10 string nullable The ICD-10 code. Example: E11.9
     * @bodyParam clinical_indication string nullable Clinical indication for the exam. Example: Acompanhamento de diabetes mellitus tipo 2.
     * @bodyParam medical_report array nullable Medical report attached to the request. Example: {"template_id": null, "body": "Atesto que o paciente..."}
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "model_id": null,
     *     "cid_10": "E11.9",
     *     "clinical_indication": "Acompanhamento de diabetes mellitus tipo 2.",
     *     "items": [{"id": "hemograma", "name": "Hemograma completo", "tuss_code": "40302566", "selected": true}],
     *     "medical_report": null,
     *     "printed_at": null,
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar solicitações de exame de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo itens é obrigatório.", "errors": {"items": ["O campo itens é obrigatório."]}}
     */
    public function store(StoreExamRequestRequest $request, int $medicalRecordId): JsonResponse
    {
        $prontuario = $this->examRequestService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('view', $prontuario);

        $dto = CreateExamRequestDTO::fromRequest($request);
        $examRequest = $this->examRequestService->create($medicalRecordId, $dto);

        return (new ExamRequestResource($examRequest))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing exam request.
     *
     * @authenticated
     *
     * @group Exam Requests
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The exam request ID. Example: 1
     *
     * @bodyParam items array List of exam items. Example: [{"id":"glicemia","name":"Glicemia em jejum","tuss_code":"40302213","selected":true}]
     * @bodyParam cid_10 string nullable The ICD-10 code. Example: E11.9
     * @bodyParam clinical_indication string nullable Clinical indication for the exam. Example: Controle de glicemia.
     * @bodyParam medical_report array nullable Medical report attached to the request.
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "model_id": null,
     *     "cid_10": "E11.9",
     *     "clinical_indication": "Controle de glicemia.",
     *     "items": [{"id": "glicemia", "name": "Glicemia em jejum", "tuss_code": "40302213", "selected": true}],
     *     "medical_report": null,
     *     "printed_at": null,
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Solicitação de exame não encontrada."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar solicitações de exame de um prontuário finalizado."}
     */
    public function update(UpdateExamRequestRequest $request, int $medicalRecordId, int $id): ExamRequestResource
    {
        $examRequest = $this->examRequestService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('update', $examRequest);

        $dto = UpdateExamRequestDTO::fromRequest($request);
        $examRequest = $this->examRequestService->update($examRequest, $dto);

        return new ExamRequestResource($examRequest);
    }

    /**
     * Delete an exam request.
     *
     * @authenticated
     *
     * @group Exam Requests
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The exam request ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Solicitação de exame excluída com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Solicitação de exame não encontrada."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar solicitações de exame de um prontuário finalizado."}
     */
    public function destroy(Request $request, int $medicalRecordId, int $id): JsonResponse
    {
        $examRequest = $this->examRequestService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('delete', $examRequest);

        $this->examRequestService->delete($examRequest);

        return response()->json(['message' => 'Solicitação de exame excluída com sucesso.']);
    }

    /**
     * Mark an exam request as printed.
     *
     * @authenticated
     *
     * @group Exam Requests
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The exam request ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "model_id": null,
     *     "cid_10": "E11.9",
     *     "clinical_indication": "Acompanhamento de diabetes mellitus tipo 2.",
     *     "items": [{"id": "hemograma", "name": "Hemograma completo", "tuss_code": "40302566", "selected": true}],
     *     "medical_report": null,
     *     "printed_at": "2026-03-10T11:00:00.000000Z",
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T11:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Solicitação de exame não encontrada."}
     */
    public function print(Request $request, int $medicalRecordId, int $id): ExamRequestResource
    {
        $examRequest = $this->examRequestService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('update', $examRequest);

        $examRequest = $this->examRequestService->markAsPrinted($examRequest);

        return new ExamRequestResource($examRequest);
    }
}
