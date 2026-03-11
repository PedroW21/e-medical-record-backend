<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreatePrescriptionTemplateDTO;
use App\Modules\MedicalRecord\DTOs\UpdatePrescriptionTemplateDTO;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\StorePrescriptionTemplateRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionTemplateRequest;
use App\Modules\MedicalRecord\Http\Resources\PrescriptionTemplateResource;
use App\Modules\MedicalRecord\Services\PrescriptionTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PrescriptionTemplateController
{
    public function __construct(
        private readonly PrescriptionTemplateService $prescriptionTemplateService,
    ) {}

    /**
     * List prescription templates for the authenticated user.
     *
     * @authenticated
     *
     * @group Prescription Templates
     *
     * @queryParam subtype string Filter templates by subtype. Example: allopathic
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Antibiótico padrão",
     *       "subtype": "allopathic",
     *       "tags": ["infecção", "rotina"],
     *       "items": [{"medication_name": "Amoxicilina 500mg", "dosage": "1 comprimido", "frequency": "8/8h", "duration": "7 dias"}],
     *       "created_at": "2026-03-10T10:00:00.000000Z",
     *       "updated_at": "2026-03-10T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $subtypeValue = $request->query('subtype');
        $subtipo = $subtypeValue ? PrescriptionSubType::tryFrom((string) $subtypeValue) : null;

        $templates = $this->prescriptionTemplateService->listForUser(
            userId: $request->user()->id,
            subtipo: $subtipo,
        );

        return PrescriptionTemplateResource::collection($templates);
    }

    /**
     * Create a new prescription template.
     *
     * @authenticated
     *
     * @group Prescription Templates
     *
     * @bodyParam name string required Template name. Example: Antibiótico padrão
     * @bodyParam subtype string required Prescription subtype. Example: allopathic
     * @bodyParam items array required List of prescription items (min 1). Example: [{"medication_name":"Amoxicilina 500mg","dosage":"1 comprimido","frequency":"8/8h","duration":"7 dias"}]
     * @bodyParam tags string[] nullable Tags for categorization. Example: ["infecção", "rotina"]
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Antibiótico padrão",
     *     "subtype": "allopathic",
     *     "tags": ["infecção", "rotina"],
     *     "items": [{"medication_name": "Amoxicilina 500mg", "dosage": "1 comprimido", "frequency": "8/8h", "duration": "7 dias"}],
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 422 scenario="Validation Error" {"message": "O campo nome é obrigatório.", "errors": {"name": ["O campo nome é obrigatório."]}}
     */
    public function store(StorePrescriptionTemplateRequest $request): JsonResponse
    {
        $dto = CreatePrescriptionTemplateDTO::fromRequest($request);
        $template = $this->prescriptionTemplateService->create(
            userId: $request->user()->id,
            dto: $dto,
        );

        return (new PrescriptionTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a prescription template.
     *
     * @authenticated
     *
     * @group Prescription Templates
     *
     * @urlParam id int required The template ID. Example: 1
     *
     * @bodyParam name string Template name. Example: Antibiótico atualizado
     * @bodyParam items array List of prescription items. Example: [{"medication_name":"Dipirona 500mg","dosage":"1 comprimido","frequency":"6/6h","duration":"5 dias"}]
     * @bodyParam tags string[] nullable Tags for categorization. Example: ["dor", "febre"]
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Antibiótico atualizado",
     *     "subtype": "allopathic",
     *     "tags": ["dor", "febre"],
     *     "items": [{"medication_name": "Dipirona 500mg", "dosage": "1 comprimido", "frequency": "6/6h", "duration": "5 dias"}],
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Modelo de prescrição não encontrado."}
     * @response 422 scenario="Validation Error" {"message": "O campo nome não pode ter mais de 255 caracteres.", "errors": {"name": ["O campo nome não pode ter mais de 255 caracteres."]}}
     */
    public function update(UpdatePrescriptionTemplateRequest $request, int $id): PrescriptionTemplateResource
    {
        $template = $this->prescriptionTemplateService->findOrFail($id);

        Gate::authorize('update', $template);

        $dto = UpdatePrescriptionTemplateDTO::fromRequest($request);
        $template = $this->prescriptionTemplateService->update($id, $dto);

        return new PrescriptionTemplateResource($template);
    }

    /**
     * Delete a prescription template.
     *
     * @authenticated
     *
     * @group Prescription Templates
     *
     * @urlParam id int required The template ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Modelo de prescrição excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Modelo de prescrição não encontrado."}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $template = $this->prescriptionTemplateService->findOrFail($id);

        Gate::authorize('delete', $template);

        $this->prescriptionTemplateService->delete($id);

        return response()->json(['message' => 'Modelo de prescrição excluído com sucesso.']);
    }
}
