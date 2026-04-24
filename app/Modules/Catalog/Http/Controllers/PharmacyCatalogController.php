<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Resources\PharmacyResource;
use App\Modules\Catalog\Models\Farmacia;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PharmacyCatalogController
{
    /**
     * List partner pharmacies available in the injectable catalog.
     *
     * @authenticated
     *
     * @group Catalog
     *
     * @responseHeader ETag Weak validator hash of the response body. Example: W/"a1b2c3d4e5f67890abcdef1234567890"
     * @responseHeader Cache-Control Example: private, must-revalidate
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id": "victa", "name": "Victa", "color": "#3B82F6"},
     *     {"id": "pineda", "name": "Pineda", "color": "#10B981"},
     *     {"id": "healthtech", "name": "Health Tech", "color": "#F59E0B"}
     *   ]
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(): AnonymousResourceCollection
    {
        $pharmacies = Farmacia::query()->orderBy('nome')->get();

        return PharmacyResource::collection($pharmacies);
    }
}
