<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\Http\Requests\ListLabCatalogRequest;
use App\Modules\MedicalRecord\Http\Resources\LabCatalogResource;
use App\Modules\MedicalRecord\Http\Resources\LabPanelResource;
use App\Modules\MedicalRecord\Services\LabCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LabCatalogController
{
    public function __construct(
        private readonly LabCatalogService $labCatalogService,
    ) {}

    /**
     * List all lab exams from the catalog.
     *
     * Returns a paginated list of catalog lab exams, optionally filtered by name or category.
     *
     * @authenticated
     *
     * @group Lab Catalog
     *
     * @queryParam search string Filter by exam name. Example: Hemoglobina
     * @queryParam category string Filter by category slug. Example: hematology
     * @queryParam per_page int Number of items per page (max 100). Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "hemo-hemoglobina",
     *       "name": "Hemoglobina",
     *       "unit": "g/dL",
     *       "reference_range": "12.0 - 17.5",
     *       "category": "hematology"
     *     }
     *   ],
     *   "links": {"first": "...", "last": "...", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 422 scenario="Validation Error" {"message": "A categoria informada é inválida.", "errors": {"category": ["A categoria informada é inválida."]}}
     */
    public function indexCatalog(ListLabCatalogRequest $request): AnonymousResourceCollection
    {
        $items = $this->labCatalogService->listCatalog(
            search: $request->validated('search'),
            category: $request->validated('category'),
            perPage: (int) ($request->validated('per_page') ?? 15),
        );

        return LabCatalogResource::collection($items);
    }

    /**
     * Get a single lab exam from the catalog.
     *
     * @authenticated
     *
     * @group Lab Catalog
     *
     * @urlParam id string required The catalog exam ID (slug). Example: hemo-hemoglobina
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "hemo-hemoglobina",
     *     "name": "Hemoglobina",
     *     "unit": "g/dL",
     *     "reference_range": "12.0 - 17.5",
     *     "category": "hematology"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 404 scenario="Not Found" {"message": "Exame laboratorial não encontrado no catálogo."}
     */
    public function showCatalog(string $id): LabCatalogResource
    {
        $exam = $this->labCatalogService->findCatalogOrFail($id);

        return new LabCatalogResource($exam);
    }

    /**
     * List all lab panels.
     *
     * Returns all available lab panels, optionally filtered by category.
     *
     * @authenticated
     *
     * @group Lab Catalog
     *
     * @queryParam search string Filter by exam name. Example: Hemograma
     * @queryParam category string Filter by category slug. Example: hematology
     * @queryParam per_page int Number of items per page (max 100). Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "hemograma-completo",
     *       "name": "Hemograma Completo",
     *       "category": "hematology",
     *       "analytes": [
     *         {"id": "hemo-hemoglobina", "name": "Hemoglobina", "unit": "g/dL", "reference_range": "12.0 - 17.5"},
     *         {"id": "hemo-hematocrito", "name": "Hematócrito", "unit": "%", "reference_range": "36 - 52"}
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 422 scenario="Validation Error" {"message": "A categoria informada é inválida.", "errors": {"category": ["A categoria informada é inválida."]}}
     */
    public function indexPanels(ListLabCatalogRequest $request): AnonymousResourceCollection
    {
        $panels = $this->labCatalogService->listPanels(
            category: $request->validated('category'),
        );

        return LabPanelResource::collection($panels);
    }

    /**
     * Get a single lab panel with its analytes.
     *
     * @authenticated
     *
     * @group Lab Catalog
     *
     * @urlParam id string required The panel ID (slug). Example: hemograma-completo
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "hemograma-completo",
     *     "name": "Hemograma Completo",
     *     "category": "hematology",
     *     "analytes": [
     *       {"id": "hemo-hemoglobina", "name": "Hemoglobina", "unit": "g/dL", "reference_range": "12.0 - 17.5"},
     *       {"id": "hemo-hematocrito", "name": "Hematócrito", "unit": "%", "reference_range": "36 - 52"}
     *     ]
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 404 scenario="Not Found" {"message": "Painel laboratorial não encontrado."}
     */
    public function showPanel(string $id): LabPanelResource
    {
        $panel = $this->labCatalogService->findPanelOrFail($id);

        return new LabPanelResource($panel);
    }
}
