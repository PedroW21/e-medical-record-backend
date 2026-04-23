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
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id": "saude_hepatica", "label": "Saúde Hepática / Detoxificação"},
     *     {"id": "cardiologia", "label": "Cardiologia"}
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = CategoriaTerapeutica::query()->orderBy('nome')->get();

        return TherapeuticCategoryResource::collection($categories);
    }
}
