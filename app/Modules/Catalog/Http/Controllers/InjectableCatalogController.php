<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Resources\InjectableResource;
use App\Modules\Catalog\Models\Injetavel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InjectableCatalogController
{
    /**
     * List injectable drugs available in the catalog.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @queryParam pharmacy_id string Filter by pharmacy id. Example: victa
     * @queryParam search string Search the injectable name. Example: magnesio
     * @queryParam per_page int Items per page (1-100). Example: 50
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "victa-magnesio",
     *       "pharmacy_id": "victa",
     *       "name": "Magnésio",
     *       "dosage": "400mg",
     *       "volume": "1mL",
     *       "exclusive_route": null,
     *       "composition": null,
     *       "is_blend": false,
     *       "allowed_routes": ["im", "ev"]
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 50, "total": 569}
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'pharmacy_id' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Injetavel::query()->orderBy('nome');

        if (! empty($validated['pharmacy_id'])) {
            $query->where('farmacia_id', $validated['pharmacy_id']);
        }

        if (! empty($validated['search'])) {
            $term = mb_strtolower((string) $validated['search']);
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.$term.'%']);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);

        return InjectableResource::collection($query->paginate($perPage));
    }

    /**
     * Retrieve a single injectable drug by id.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @urlParam id string required The injectable id. Example: victa-magnesio
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "victa-magnesio",
     *     "pharmacy_id": "victa",
     *     "name": "Magnésio",
     *     "dosage": "400mg",
     *     "volume": "1mL",
     *     "exclusive_route": null,
     *     "composition": null,
     *     "is_blend": false,
     *     "allowed_routes": ["im", "ev"]
     *   }
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 404 scenario="Not found" {"message": "Injetável não encontrado."}
     */
    public function show(string $id): InjectableResource
    {
        $injectable = Injetavel::query()->find($id);

        if ($injectable === null) {
            throw (new ModelNotFoundException('Injetável não encontrado.'))->setModel(Injetavel::class, [$id]);
        }

        return new InjectableResource($injectable);
    }
}
