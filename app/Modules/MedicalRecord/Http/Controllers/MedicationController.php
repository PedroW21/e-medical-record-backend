<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\MedicationFilterDTO;
use App\Modules\MedicalRecord\Http\Requests\ListMedicationRequest;
use App\Modules\MedicalRecord\Http\Resources\MedicationResource;
use App\Modules\MedicalRecord\Services\MedicationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MedicationController
{
    public function __construct(
        private readonly MedicationService $medicationService,
    ) {}

    /**
     * List medications from the catalog.
     *
     * Returns a paginated list of active medications, optionally filtered by name/active ingredient or controlled status.
     *
     * @authenticated
     *
     * @group Medications
     *
     * @queryParam search string Filter by medication name or active ingredient. Example: Amoxicilina
     * @queryParam controlled boolean Filter by controlled status. Example: false
     * @queryParam per_page int Number of items per page (max 100). Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Amoxicilina 500mg",
     *       "active_ingredient": "Amoxicilina",
     *       "presentation": "Cápsula",
     *       "manufacturer": "EMS",
     *       "anvisa_code": "1234567890123",
     *       "anvisa_list": null,
     *       "is_controlled": false
     *     }
     *   ],
     *   "links": {"first": "...", "last": "...", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 422 scenario="Validation Error" {"message": "O campo itens por página deve ser no máximo 100.", "errors": {"per_page": ["O campo itens por página deve ser no máximo 100."]}}
     */
    public function index(ListMedicationRequest $request): AnonymousResourceCollection
    {
        $dto = MedicationFilterDTO::fromRequest($request);
        $medications = $this->medicationService->list($dto);

        return MedicationResource::collection($medications);
    }

    /**
     * Get a single medication from the catalog.
     *
     * @authenticated
     *
     * @group Medications
     *
     * @urlParam id int required The medication ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Amoxicilina 500mg",
     *     "active_ingredient": "Amoxicilina",
     *     "presentation": "Cápsula",
     *     "manufacturer": "EMS",
     *     "anvisa_code": "1234567890123",
     *     "anvisa_list": null,
     *     "is_controlled": false
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 404 scenario="Not Found" {"message": "Medicamento não encontrado."}
     */
    public function show(int $id): MedicationResource
    {
        $medication = $this->medicationService->findOrFail($id);

        return new MedicationResource($medication);
    }
}
