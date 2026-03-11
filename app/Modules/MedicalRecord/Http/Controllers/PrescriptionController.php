<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreatePrescriptionDTO;
use App\Modules\MedicalRecord\DTOs\UpdatePrescriptionDTO;
use App\Modules\MedicalRecord\Http\Requests\StorePrescriptionRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionRequest;
use App\Modules\MedicalRecord\Http\Resources\PrescriptionResource;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Services\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PrescriptionController
{
    public function __construct(
        private readonly PrescriptionService $prescriptionService,
    ) {}

    /**
     * List all prescriptions for a medical record.
     *
     * @authenticated
     *
     * @group Prescriptions
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "medical_record_id": 1,
     *       "subtype": "allopathic",
     *       "recipe_type": "normal",
     *       "recipe_type_override": false,
     *       "items": [{"medication_name": "Amoxicilina 500mg", "dosage": "1 comprimido", "frequency": "8/8h", "duration": "7 dias"}],
     *       "notes": null,
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
        $prontuario = $this->prescriptionService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('view', $prontuario);

        $prescriptions = $this->prescriptionService->listByMedicalRecord($medicalRecordId);

        return PrescriptionResource::collection($prescriptions);
    }

    /**
     * Create a new prescription for a medical record.
     *
     * @authenticated
     *
     * @group Prescriptions
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @bodyParam subtype string required Prescription subtype. Example: allopathic
     * @bodyParam items array required List of prescription items (min 1). Example: [{"medication_name":"Amoxicilina 500mg","dosage":"1 comprimido","frequency":"8/8h","duration":"7 dias"}]
     * @bodyParam notes string nullable Additional notes. Example: Tomar com alimento.
     * @bodyParam recipe_type_override boolean nullable Whether to override the auto-detected recipe type. Example: false
     * @bodyParam recipe_type string nullable Required when recipe_type_override is true. Example: normal
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "subtype": "allopathic",
     *     "recipe_type": "normal",
     *     "recipe_type_override": false,
     *     "items": [{"medication_name": "Amoxicilina 500mg", "dosage": "1 comprimido", "frequency": "8/8h", "duration": "7 dias"}],
     *     "notes": "Tomar com alimento.",
     *     "printed_at": null,
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar prescrições de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo subtipo é obrigatório.", "errors": {"subtype": ["O campo subtipo é obrigatório."]}}
     */
    public function store(StorePrescriptionRequest $request, int $medicalRecordId): JsonResponse
    {
        $prontuario = $this->prescriptionService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('create', [Prescricao::class, $prontuario]);

        $dto = CreatePrescriptionDTO::fromRequest($request);
        $prescription = $this->prescriptionService->create($medicalRecordId, $dto);

        return (new PrescriptionResource($prescription))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing prescription.
     *
     * @authenticated
     *
     * @group Prescriptions
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The prescription ID. Example: 1
     *
     * @bodyParam subtype string Prescription subtype. Example: allopathic
     * @bodyParam items array List of prescription items. Example: [{"medication_name":"Dipirona 500mg","dosage":"1 comprimido","frequency":"6/6h","duration":"5 dias"}]
     * @bodyParam notes string nullable Additional notes. Example: null
     * @bodyParam recipe_type_override boolean nullable Whether to override the auto-detected recipe type. Example: false
     * @bodyParam recipe_type string nullable Required when recipe_type_override is true. Example: white_c1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "subtype": "allopathic",
     *     "recipe_type": "normal",
     *     "recipe_type_override": false,
     *     "items": [{"medication_name": "Dipirona 500mg", "dosage": "1 comprimido", "frequency": "6/6h", "duration": "5 dias"}],
     *     "notes": null,
     *     "printed_at": null,
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prescrição não encontrada."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar prescrições de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O subtipo informado é inválido.", "errors": {"subtype": ["O subtipo informado é inválido."]}}
     */
    public function update(UpdatePrescriptionRequest $request, int $medicalRecordId, int $id): PrescriptionResource
    {
        $prescription = $this->prescriptionService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('update', $prescription);

        $dto = UpdatePrescriptionDTO::fromRequest($request);
        $prescription = $this->prescriptionService->update($id, $dto);

        return new PrescriptionResource($prescription);
    }

    /**
     * Delete a prescription.
     *
     * @authenticated
     *
     * @group Prescriptions
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The prescription ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Prescrição excluída com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prescrição não encontrada."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar prescrições de um prontuário finalizado."}
     */
    public function destroy(Request $request, int $medicalRecordId, int $id): JsonResponse
    {
        $prescription = $this->prescriptionService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('delete', $prescription);

        $this->prescriptionService->delete($id);

        return response()->json(['message' => 'Prescrição excluída com sucesso.']);
    }
}
