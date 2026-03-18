<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Http\Requests\StoreExamResultRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateExamResultRequest;
use App\Modules\MedicalRecord\Services\ExamResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @authenticated
 *
 * @group Exam Results
 */
final class ExamResultController
{
    public function __construct(
        private readonly ExamResultService $examResultService,
    ) {}

    /**
     * List all exam results of a given type for a medical record.
     *
     * Returns all results for the specified exam type, ordered by date descending.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "medical_record_id": 1,
     *       "date": "2026-03-10",
     *       "rhythm": "sinusal",
     *       "heart_rate": 72,
     *       "pr_interval": 160,
     *       "qrs_duration": 90,
     *       "qt_interval": 400,
     *       "qtc_interval": 420,
     *       "axis": "normal",
     *       "interpretation": "Ritmo sinusal normal.",
     *       "created_at": "2026-03-10T10:00:00.000000Z",
     *       "updated_at": "2026-03-10T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     */
    public function index(Request $request, int $medicalRecordId, string $examType): AnonymousResourceCollection
    {
        $type = ExamType::from($examType);
        $prontuario = $this->examResultService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('viewAny', [$type->modelClass(), $prontuario]);

        $results = $this->examResultService->listByMedicalRecord($type, $medicalRecordId);
        $resourceClass = $type->resourceClass();

        return $resourceClass::collection($results);
    }

    /**
     * Store a new exam result of a given type for a medical record.
     *
     * Creates a new structured exam result record. The required fields depend on the exam type.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     *
     * @bodyParam date string required The exam date (YYYY-MM-DD). Example: 2026-03-10
     * @bodyParam rhythm string nullable ECG-specific: cardiac rhythm. Example: sinusal
     * @bodyParam heart_rate int nullable ECG-specific: heart rate in bpm. Example: 72
     * @bodyParam interpretation string nullable Free interpretation text. Example: Ritmo sinusal normal.
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "date": "2026-03-10",
     *     "rhythm": "sinusal",
     *     "heart_rate": 72,
     *     "pr_interval": 160,
     *     "qrs_duration": 90,
     *     "qt_interval": 400,
     *     "qtc_interval": 420,
     *     "axis": "normal",
     *     "interpretation": "Ritmo sinusal normal.",
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo data é obrigatório.", "errors": {"date": ["O campo data é obrigatório."]}}
     */
    public function store(StoreExamResultRequest $request, int $medicalRecordId, string $examType): JsonResponse
    {
        $type = ExamType::from($examType);
        $prontuario = $this->examResultService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('create', [$type->modelClass(), $prontuario]);

        $result = $this->examResultService->store($type, $medicalRecordId, $request->validated());
        $resourceClass = $type->resourceClass();

        return (new $resourceClass($result))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing exam result of a given type.
     *
     * Partially updates an exam result. All fields are optional; only provided fields are updated.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     * @urlParam id int required The exam result ID. Example: 1
     *
     * @bodyParam date string nullable The exam date (YYYY-MM-DD). Example: 2026-03-11
     * @bodyParam rhythm string nullable ECG-specific: cardiac rhythm. Example: sinusal
     * @bodyParam heart_rate int nullable ECG-specific: heart rate in bpm. Example: 75
     * @bodyParam interpretation string nullable Free interpretation text. Example: Ritmo sinusal com frequência limítrofe.
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "date": "2026-03-11",
     *     "rhythm": "sinusal",
     *     "heart_rate": 75,
     *     "pr_interval": 160,
     *     "qrs_duration": 90,
     *     "qt_interval": 400,
     *     "qtc_interval": 420,
     *     "axis": "normal",
     *     "interpretation": "Ritmo sinusal com frequência limítrofe.",
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-11T09:15:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Resultado de exame não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo data deve ser uma data válida.", "errors": {"date": ["O campo data deve ser uma data válida."]}}
     */
    public function update(UpdateExamResultRequest $request, int $medicalRecordId, string $examType, int $id): JsonResponse
    {
        $type = ExamType::from($examType);
        $result = $this->examResultService->findForMedicalRecordOrFail($type, $id, $medicalRecordId);

        Gate::authorize('update', $result);

        $updated = $this->examResultService->update($type, $id, $medicalRecordId, $request->validated());
        $resourceClass = $type->resourceClass();

        return (new $resourceClass($updated))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete an exam result of a given type.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     * @urlParam id int required The exam result ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Resultado de ECG excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Resultado de exame não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados de um prontuário finalizado."}
     */
    public function destroy(Request $request, int $medicalRecordId, string $examType, int $id): JsonResponse
    {
        $type = ExamType::from($examType);
        $result = $this->examResultService->findForMedicalRecordOrFail($type, $id, $medicalRecordId);

        Gate::authorize('delete', $result);

        $this->examResultService->delete($type, $id, $medicalRecordId);

        return response()->json(['message' => $type->deletedMessage()]);
    }
}
