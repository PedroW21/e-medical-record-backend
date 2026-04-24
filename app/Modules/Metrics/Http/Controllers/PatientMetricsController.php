<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Http\Controllers;

use App\Modules\Metrics\Http\Resources\MetricHistoryResource;
use App\Modules\Metrics\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PatientMetricsController
{
    public function __construct(private readonly MetricsService $metricsService) {}

    /**
     * List the wide-format evolution series for a patient.
     *
     * @authenticated
     *
     * @group Metrics
     *
     * @urlParam id int required The patient id. Example: 12
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "patient_id": 12,
     *       "date": "2026-01-10",
     *       "recorded_by": 7,
     *       "values": {"hemoglobin": 13.5, "glucose": 92, "tsh": 1.8}
     *     },
     *     {
     *       "id": 2,
     *       "patient_id": 12,
     *       "date": "2026-03-15",
     *       "recorded_by": 7,
     *       "values": {"hemoglobin": 13.8, "ldl": 110}
     *     }
     *   ],
     *   "total": 2
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 404 scenario="Not found" {"message": "Paciente nao encontrado."}
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $payload = $this->metricsService->listForPatient(
            userId: $request->user()->id,
            patientId: $id,
        );

        return response()->json($payload);
    }

    /**
     * Retrieve the history for a single metric of a patient.
     *
     * @authenticated
     *
     * @group Metrics
     *
     * @urlParam id int required The patient id. Example: 12
     * @urlParam metricId string required The metric id (see Metrics frontend config). Example: hemoglobin
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "metric_id": "hemoglobin",
     *     "metric_name": "Hemoglobina",
     *     "unit": "g/dL",
     *     "ref_min": 12.0,
     *     "ref_max": 17.5,
     *     "color": "#DC2626",
     *     "history": [
     *       {"date": "2026-01-10", "value": 13.5},
     *       {"date": "2026-03-15", "value": 13.8}
     *     ]
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 404 scenario="Unknown metric" {"message": "Metrica nao encontrada."}
     * @response 404 scenario="Unknown patient" {"message": "Paciente nao encontrado."}
     */
    public function history(Request $request, int $id, string $metricId): MetricHistoryResource
    {
        $payload = $this->metricsService->historyForPatient(
            userId: $request->user()->id,
            patientId: $id,
            metricId: $metricId,
        );

        return new MetricHistoryResource($payload);
    }
}
