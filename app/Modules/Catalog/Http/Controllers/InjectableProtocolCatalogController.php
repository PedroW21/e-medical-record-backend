<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Enums\InjectableProtocolRoute;
use App\Modules\Catalog\Http\Resources\InjectableProtocolResource;
use App\Modules\Catalog\Models\InjetavelProtocolo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InjectableProtocolCatalogController
{
    /**
     * List injectable protocols in the catalog.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @queryParam pharmacy_id string Filter by pharmacy id. Example: victa
     * @queryParam therapeutic_category_id string Filter by therapeutic category. Example: cardiologia
     * @queryParam route string Filter by route (`im`, `ev`, `combined`). Example: ev
     * @queryParam search string Search by protocol name. Example: antioxidante
     * @queryParam per_page int Items per page (1-100). Example: 25
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "victa-proto-ev-antioxidante-1",
     *       "pharmacy_id": "victa",
     *       "therapeutic_category_id": "envelhecimento",
     *       "name": "Antioxidante 01",
     *       "route": "ev",
     *       "application_notes": null
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 25, "total": 300}
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'pharmacy_id' => ['nullable', 'string'],
            'therapeutic_category_id' => ['nullable', 'string'],
            'route' => ['nullable', 'string', 'in:'.implode(',', InjectableProtocolRoute::values())],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = InjetavelProtocolo::query()->orderBy('nome');

        if (! empty($validated['pharmacy_id'])) {
            $query->where('farmacia_id', $validated['pharmacy_id']);
        }

        if (! empty($validated['therapeutic_category_id'])) {
            $query->where('categoria_terapeutica_id', $validated['therapeutic_category_id']);
        }

        if (! empty($validated['route'])) {
            $query->where('via', $validated['route']);
        }

        if (! empty($validated['search'])) {
            $term = mb_strtolower((string) $validated['search']);
            $query->whereRaw('LOWER(nome) LIKE ?', ['%'.$term.'%']);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);

        return InjectableProtocolResource::collection($query->paginate($perPage));
    }

    /**
     * Retrieve a protocol with its ordered components.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @urlParam id string required The protocol id. Example: victa-proto-ev-antioxidante-1
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "victa-proto-ev-antioxidante-1",
     *     "pharmacy_id": "victa",
     *     "therapeutic_category_id": "envelhecimento",
     *     "name": "Antioxidante 01",
     *     "route": "ev",
     *     "application_notes": null,
     *     "components": [
     *       {"order": 1, "farmaco_name": "N-Acetil-Cisteína", "dosage": "300mg/2mL", "ampoule_count": 1, "route": null}
     *     ]
     *   }
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 404 scenario="Not found" {"message": "Protocolo injetável não encontrado."}
     */
    public function show(string $id): InjectableProtocolResource
    {
        $protocol = InjetavelProtocolo::query()->with('componentes')->find($id);

        if ($protocol === null) {
            throw (new ModelNotFoundException('Protocolo injetável não encontrado.'))->setModel(InjetavelProtocolo::class, [$id]);
        }

        return new InjectableProtocolResource($protocol);
    }
}
