<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Metrics\Http\Resources\MetricDefinitionResource;
use App\Modules\Metrics\Registry\MetricRegistry;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MetricDefinitionsController extends Controller
{
    /**
     * List all metric definitions exposed to the frontend evolution charts.
     *
     * Returns the full set of MVP metrics (Phase 7) grouped by category in
     * stable display order. The response is cached via ETag — clients can
     * revalidate with `If-None-Match` and receive a 304 when nothing changed.
     *
     * @group Metrics
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id":"hemoglobin","category":"hemogram","name":"Hemoglobina","unit":"g/dL","ref_min":12.0,"ref_max":17.5,"color":"#DC2626"},
     *     {"id":"glucose","category":"biochemistry","name":"Glicemia","unit":"mg/dL","ref_min":70.0,"ref_max":99.0,"color":"#059669"},
     *     {"id":"tsh","category":"thyroid","name":"TSH","unit":"mUI/L","ref_min":0.4,"ref_max":4.0,"color":"#6366F1"}
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return MetricDefinitionResource::collection(
            array_values(MetricRegistry::all()),
        );
    }
}
