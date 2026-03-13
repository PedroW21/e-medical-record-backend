<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\StoreLabResultDTO;
use App\Modules\MedicalRecord\DTOs\UpdateLabValueDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreLabResultRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateLabValueRequest;
use App\Modules\MedicalRecord\Http\Resources\LabResultGroupedResource;
use App\Modules\MedicalRecord\Http\Resources\LabResultResource;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\MedicalRecord\Services\LabResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class LabResultController
{
    public function __construct(
        private readonly LabResultService $labResultService,
    ) {}

    /**
     * List all lab results for a medical record (v2 grouped format).
     *
     * Returns lab values grouped by collection date, then split into panels and loose entries.
     *
     * @authenticated
     *
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "date": "2026-03-10",
     *       "panels": [
     *         {
     *           "panel_id": "hemograma-completo",
     *           "panel_name": "Hemograma Completo",
     *           "is_custom": false,
     *           "values": [
     *             {"id": 1, "analyte_id": "hemo-hemoglobina", "value": "14.5"}
     *           ]
     *         }
     *       ],
     *       "loose": [
     *         {
     *           "id": 2,
     *           "name": "Exame especial XYZ",
     *           "value": "Negativo",
     *           "unit": null,
     *           "reference_range": null
     *         }
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     */
    public function index(Request $request, int $medicalRecordId): JsonResponse
    {
        $prontuario = $this->labResultService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('view', $prontuario);

        $values = $this->labResultService->listByMedicalRecord($medicalRecordId);
        $grouped = LabResultGroupedResource::fromCollection($values);

        return response()->json(['data' => $grouped]);
    }

    /**
     * Store lab results for a medical record in v2 panel format.
     *
     * Accepts a batch of panel-based and loose lab entries for a single collection date.
     * Panel entries are expanded into individual analyte rows linked to the catalog.
     *
     * @authenticated
     *
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @bodyParam date string required The collection date (YYYY-MM-DD). Example: 2026-03-10
     * @bodyParam panels array nullable List of panel entries. Example: [{"panel_id":"hemograma-completo","values":[{"analyte_id":"hemo-hemoglobina","value":"14.5"}]}]
     * @bodyParam loose array nullable List of loose (free-form) lab entries. Example: [{"name":"Exame especial XYZ","value":"Negativo","unit":null,"reference_range":null}]
     *
     * @response 201 scenario="Created" {
     *   "data": [
     *     {
     *       "date": "2026-03-10",
     *       "panels": [
     *         {
     *           "panel_id": "hemograma-completo",
     *           "panel_name": "Hemograma Completo",
     *           "is_custom": false,
     *           "values": [
     *             {"id": 1, "analyte_id": "hemo-hemoglobina", "value": "14.5"}
     *           ]
     *         }
     *       ],
     *       "loose": [
     *         {
     *           "id": 2,
     *           "name": "Exame especial XYZ",
     *           "value": "Negativo",
     *           "unit": null,
     *           "reference_range": null
     *         }
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados laboratoriais de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo data é obrigatório.", "errors": {"date": ["O campo data é obrigatório."]}}
     */
    public function store(StoreLabResultRequest $request, int $medicalRecordId): JsonResponse
    {
        $prontuario = $this->labResultService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('create', [ValorLaboratorial::class, $prontuario]);

        $dto = StoreLabResultDTO::fromRequest($request);
        $created = $this->labResultService->batchStore($medicalRecordId, $dto);
        $grouped = LabResultGroupedResource::fromCollection($created);

        return response()->json(['data' => $grouped], 201);
    }

    /**
     * Update a single lab value.
     *
     * Updates an existing lab value entry. Only value, unit, reference range, and collection date may be changed.
     *
     * @authenticated
     *
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The lab value ID. Example: 1
     *
     * @bodyParam value string nullable The updated result value. Example: 15.0
     * @bodyParam unit string nullable The updated measurement unit. Example: g/dL
     * @bodyParam reference_range string nullable The updated reference range. Example: 12.0 - 17.5
     * @bodyParam collection_date string nullable The updated collection date (YYYY-MM-DD). Example: 2026-03-10
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "patient_id": 5,
     *     "analyte_id": "hemo-hemoglobina",
     *     "analyte_name": "Hemoglobina",
     *     "loose_name": null,
     *     "value": "15.0",
     *     "unit": "g/dL",
     *     "reference_range": "12.0 - 17.5",
     *     "collection_date": "2026-03-10",
     *     "panel_id": "hemograma-completo",
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Valor laboratorial não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados laboratoriais de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo valor deve ser uma string.", "errors": {"value": ["O campo valor deve ser uma string."]}}
     */
    public function update(UpdateLabValueRequest $request, int $medicalRecordId, int $id): LabResultResource
    {
        $labValue = $this->labResultService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('update', $labValue);

        $dto = UpdateLabValueDTO::fromRequest($request);
        $labValue = $this->labResultService->update($id, $dto);

        return new LabResultResource($labValue);
    }

    /**
     * Delete a lab value.
     *
     * @authenticated
     *
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The lab value ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Resultado laboratorial excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Valor laboratorial não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados laboratoriais de um prontuário finalizado."}
     */
    public function destroy(Request $request, int $medicalRecordId, int $id): JsonResponse
    {
        $labValue = $this->labResultService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('delete', $labValue);

        $this->labResultService->delete($id);

        return response()->json(['message' => 'Resultado laboratorial excluído com sucesso.']);
    }
}
