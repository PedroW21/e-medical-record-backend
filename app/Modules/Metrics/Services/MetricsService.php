<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\Metrics\DTOs\MetricDefinition;
use App\Modules\Metrics\DTOs\MetricPoint;
use App\Modules\Metrics\Registry\MetricRegistry;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final class MetricsService
{
    /**
     * Build the wide-format evolution list for the given patient.
     *
     * Each row groups all lab values sharing the same `data_coleta`, mapped
     * to the frontend metric ids defined in the registry.
     *
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    public function listForPatient(int $userId, int $patientId): array
    {
        $patient = $this->requirePatient($userId, $patientId);

        /** @var Collection<int, ValorLaboratorial> $rows */
        $rows = ValorLaboratorial::query()
            ->where('paciente_id', $patient->id)
            ->whereIn('catalogo_exame_id', MetricRegistry::catalogoExameIds())
            ->whereNotNull('valor_numerico')
            ->orderBy('data_coleta')
            ->get(['id', 'paciente_id', 'catalogo_exame_id', 'data_coleta', 'valor_numerico']);

        $catalogoToMetric = MetricRegistry::catalogoToMetricMap();

        /** @var array<string, array<string, float>> $grouped */
        $grouped = [];
        foreach ($rows as $row) {
            $date = $row->data_coleta->format('Y-m-d');
            $metricId = $catalogoToMetric[(string) $row->catalogo_exame_id] ?? null;

            if ($metricId === null) {
                continue;
            }

            $grouped[$date][$metricId] = (float) $row->valor_numerico;
        }

        ksort($grouped);

        $data = [];
        $index = 0;
        foreach ($grouped as $date => $values) {
            $index++;
            $data[] = [
                'id' => $index,
                'patient_id' => $patient->id,
                'date' => $date,
                'recorded_by' => $patient->user_id,
                'values' => $values,
            ];
        }

        return [
            'data' => $data,
            'total' => count($data),
        ];
    }

    /**
     * Build a single-metric history for the given patient.
     *
     * @return array{definition: MetricDefinition, history: array<int, MetricPoint>}
     */
    public function historyForPatient(int $userId, int $patientId, string $metricId): array
    {
        $patient = $this->requirePatient($userId, $patientId);

        $definition = MetricRegistry::find($metricId);

        if ($definition === null) {
            throw (new ModelNotFoundException('Metrica nao encontrada.'))
                ->setModel(MetricDefinition::class, [$metricId]);
        }

        /** @var Collection<int, ValorLaboratorial> $rows */
        $rows = ValorLaboratorial::query()
            ->where('paciente_id', $patient->id)
            ->where('catalogo_exame_id', $definition->catalogoExameId)
            ->whereNotNull('valor_numerico')
            ->orderBy('data_coleta')
            ->get(['data_coleta', 'valor_numerico']);

        $history = $rows->map(fn (ValorLaboratorial $row): MetricPoint => new MetricPoint(
            date: $row->data_coleta->format('Y-m-d'),
            value: (float) $row->valor_numerico,
        ))->all();

        return [
            'definition' => $definition,
            'history' => $history,
        ];
    }

    /**
     * Resolve a patient owned by the authenticated doctor, or raise 404.
     */
    private function requirePatient(int $userId, int $patientId): Paciente
    {
        $patient = Paciente::query()
            ->where('id', $patientId)
            ->where('user_id', $userId)
            ->first();

        if ($patient === null) {
            throw (new ModelNotFoundException('Paciente nao encontrado.'))
                ->setModel(Paciente::class, [$patientId]);
        }

        return $patient;
    }
}
