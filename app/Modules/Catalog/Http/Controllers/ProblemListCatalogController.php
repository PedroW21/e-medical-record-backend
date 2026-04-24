<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Enums\ProblemCategory;
use App\Modules\Catalog\Http\Resources\ProblemListEntryResource;
use App\Modules\Catalog\Models\ListaProblema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProblemListCatalogController
{
    /**
     * List default problem entries used for the medical-record problem list.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @queryParam category string Filter by category. Example: metabolic
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id": "anemia", "category": "hematologic", "label": "Anemia", "variation": {"id": "target", "label": "Alvo", "options": ["within_target", "out_of_target"]}},
     *     {"id": "dm2", "category": "metabolic", "label": "DM2", "variation": {"id": "target", "label": "Alvo", "options": ["within_target", "out_of_target"]}}
     *   ]
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'in:'.implode(',', ProblemCategory::values())],
        ]);

        $query = ListaProblema::query()->orderBy('rotulo');

        if (! empty($validated['category'])) {
            $query->where('categoria', $validated['category']);
        }

        return ProblemListEntryResource::collection($query->get());
    }
}
