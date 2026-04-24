<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Resources\TherapeuticCategoryResource;
use App\Modules\Catalog\Models\CategoriaTerapeutica;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TherapeuticCategoryCatalogController
{
    /**
     * List therapeutic categories used to group injectables and protocols.
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
     *     {"id": "saude_hepatica", "label": "Saúde Hepática / Detoxificação"},
     *     {"id": "cardiologia", "label": "Cardiologia"}
     *   ]
     * }
     * @response 304 scenario="Not Modified — cached payload still valid" {}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = CategoriaTerapeutica::query()->orderBy('nome')->get();

        return TherapeuticCategoryResource::collection($categories);
    }
}
