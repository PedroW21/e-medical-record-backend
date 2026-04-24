<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Enums\MagistralType;
use App\Modules\Catalog\Http\Resources\MagistralCategoryResource;
use App\Modules\Catalog\Http\Resources\MagistralFormulaResource;
use App\Modules\Catalog\Models\MagistralCategoria;
use App\Modules\Catalog\Models\MagistralFormula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MagistralCatalogController
{
    /**
     * List magistral catalog categories, optionally filtered by type.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @queryParam type string Filter by type (`farmaco` or `alvo`). Example: farmaco
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id": "farmaco_melatonina", "type": "farmaco", "label": "Melatonina", "icon": "moon"}
     *   ]
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 422 scenario="Invalid type" {"message": "The selected type is invalid."}
     */
    public function categories(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:'.implode(',', MagistralType::values())],
        ]);

        $query = MagistralCategoria::query()->orderBy('rotulo');

        if (! empty($validated['type'])) {
            $query->where('tipo', $validated['type']);
        }

        return MagistralCategoryResource::collection($query->get());
    }

    /**
     * List magistral formulas, optionally filtered by category or name search.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @queryParam category_id string Filter by category id. Example: farmaco_melatonina
     * @queryParam search string Search the formula name (case-insensitive substring). Example: melatonin
     * @queryParam per_page int Items per page (1-100). Example: 20
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "farmaco_melatonina_duo_fast",
     *       "category_id": "farmaco_melatonina",
     *       "name": "Melatonin DUO Fast & Slow Release",
     *       "components": [
     *         {"name": "Melatonin DUO Fast & Slow Release® (ESSENTIAL)", "dose": "0,21mg"}
     *       ],
     *       "excipient": "1 frasco",
     *       "posology": "fazer uso SUBLINGUAL de 01 unidade, imediatamente antes de dormir",
     *       "instructions": null,
     *       "notes": null
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 20, "total": 263}
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function formulas(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = MagistralFormula::query()->orderBy('nome');

        if (! empty($validated['category_id'])) {
            $query->where('categoria_id', $validated['category_id']);
        }

        if (! empty($validated['search'])) {
            $term = mb_strtolower((string) $validated['search']);
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.$term.'%']);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);

        return MagistralFormulaResource::collection($query->paginate($perPage));
    }
}
