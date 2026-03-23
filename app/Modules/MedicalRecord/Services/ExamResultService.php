<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Http\Resources\ExamResultFieldMap;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExamResultService
{
    use ExamResultFieldMap;

    /**
     * Find a medical record by ID or throw a 404.
     *
     * @throws NotFoundHttpException
     */
    public function findMedicalRecordOrFail(int $medicalRecordId): Prontuario
    {
        $prontuario = Prontuario::query()->find($medicalRecordId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    /**
     * Find an exam result by ID scoped to a medical record, or throw a 404.
     *
     * For MRPA results, eager-loads the measurements relationship.
     *
     * @throws NotFoundHttpException
     */
    public function findForMedicalRecordOrFail(ExamType $examType, int $id, int $medicalRecordId): Model
    {
        $query = $examType->modelClass()::query();

        if ($examType === ExamType::Mrpa) {
            $query->with('medicoes');
        }

        $result = $query->find($id);

        if (! $result || $result->prontuario_id !== $medicalRecordId) {
            throw new NotFoundHttpException('Resultado de exame não encontrado.');
        }

        return $result;
    }

    /**
     * List all exam results for a medical record, ordered by date desc then id asc.
     *
     * For MRPA results, eager-loads the measurements relationship.
     *
     * @return Collection<int, Model>
     */
    public function listByMedicalRecord(ExamType $examType, int $medicalRecordId): Collection
    {
        $query = $examType->modelClass()::query()
            ->where('prontuario_id', $medicalRecordId)
            ->orderByDesc('data')
            ->orderBy('id');

        if ($examType === ExamType::Mrpa) {
            $query->with('medicoes');
        }

        return $query->get();
    }

    /**
     * Store a new exam result for the given medical record.
     *
     * Sets prontuario_id and paciente_id from the prontuario.
     * For MRPA, handles parent + child measurements in a transaction.
     *
     * @param  array<string, mixed>  $data  API field names
     *
     * @throws ConflictHttpException When the medical record is not in draft status
     * @throws NotFoundHttpException When the medical record is not found
     */
    public function store(ExamType $examType, int $medicalRecordId, array $data): Model
    {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        $mapped = $this->mapApiToDb($examType, $data);
        $mapped['prontuario_id'] = $prontuario->id;
        $mapped['paciente_id'] = $prontuario->paciente_id;

        if ($examType === ExamType::Mrpa) {
            $measurements = $data['measurements'] ?? [];

            return $this->storeMrpa($mapped, $measurements);
        }

        return $examType->modelClass()::query()->create($mapped);
    }

    /**
     * Update an existing exam result.
     *
     * For MRPA with measurements, replaces child records in a transaction.
     *
     * @param  array<string, mixed>  $data  API field names
     *
     * @throws ConflictHttpException When the medical record is not in draft status
     * @throws NotFoundHttpException When the exam result is not found
     */
    public function update(ExamType $examType, int $id, int $medicalRecordId, array $data): Model
    {
        $result = $this->findForMedicalRecordOrFail($examType, $id, $medicalRecordId);
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        $mapped = $this->mapApiToDb($examType, $data);

        if ($examType === ExamType::Mrpa) {
            $measurements = $data['measurements'] ?? null;

            return $this->updateMrpa($result, $mapped, $measurements);
        }

        $result->update($mapped);

        return $result->fresh();
    }

    /**
     * Delete an exam result from a medical record.
     *
     * @throws ConflictHttpException When the medical record is not in draft status
     * @throws NotFoundHttpException When the exam result is not found
     */
    public function delete(ExamType $examType, int $id, int $medicalRecordId): void
    {
        $result = $this->findForMedicalRecordOrFail($examType, $id, $medicalRecordId);
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        $result->delete();
    }

    /**
     * Map API field names to DB column names for a given exam type.
     *
     * Handles dot-notation keys for nested API objects (e.g. CarotidEcodoppler arterias,
     * Scintigraphy perfusion territories). The dot-notation key "parent.child" means
     * the API sends $data['parent']['child'] which maps to a flat DB column.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mapApiToDb(ExamType $examType, array $data): array
    {
        $map = self::apiToDbMap($examType);
        $result = [];

        foreach ($map as $apiKey => $dbColumn) {
            if (str_contains($apiKey, '.')) {
                [$parent, $child] = explode('.', $apiKey, 2);

                if (isset($data[$parent]) && is_array($data[$parent]) && array_key_exists($child, $data[$parent])) {
                    $result[$dbColumn] = $data[$parent][$child];
                }
            } elseif (array_key_exists($apiKey, $data)) {
                $result[$dbColumn] = $data[$apiKey];
            }
        }

        return $result;
    }

    /**
     * Throw a ConflictHttpException if the medical record is not in draft status.
     *
     * @throws ConflictHttpException
     */
    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar resultados de um prontuário finalizado.');
        }
    }

    /**
     * Create an MRPA result with its measurements in a single transaction.
     *
     * @param  array<string, mixed>  $data  Mapped DB column data for the parent record
     * @param  array<int, array<string, mixed>>  $measurements  Raw API measurement data
     */
    private function storeMrpa(array $data, array $measurements): ResultadoMrpa
    {
        return DB::transaction(function () use ($data, $measurements): ResultadoMrpa {
            /** @var ResultadoMrpa $mrpa */
            $mrpa = ResultadoMrpa::query()->create($data);

            foreach ($measurements as $measurement) {
                $mrpa->medicoes()->create($this->mapMeasurement($measurement));
            }

            return $mrpa->load('medicoes');
        });
    }

    /**
     * Update an MRPA result and optionally replace its measurements in a transaction.
     *
     * @param  array<string, mixed>  $data  Mapped DB column data for the parent record
     * @param  array<int, array<string, mixed>>|null  $measurements  Raw API measurement data, or null to leave unchanged
     */
    private function updateMrpa(Model $mrpa, array $data, ?array $measurements): ResultadoMrpa
    {
        return DB::transaction(function () use ($mrpa, $data, $measurements): ResultadoMrpa {
            $mrpa->update($data);

            if ($measurements !== null) {
                $mrpa->medicoes()->delete();

                foreach ($measurements as $measurement) {
                    $mrpa->medicoes()->create($this->mapMeasurement($measurement));
                }
            }

            return $mrpa->fresh()->load('medicoes');
        });
    }

    /**
     * Map API measurement field names to DB column names.
     *
     * @param  array<string, mixed>  $measurement
     * @return array<string, mixed>
     */
    private function mapMeasurement(array $measurement): array
    {
        $measurementMap = [
            'date' => 'data',
            'time' => 'hora',
            'period' => 'periodo',
            'systolic' => 'pas',
            'diastolic' => 'pad',
        ];

        $mapped = [];

        foreach ($measurementMap as $apiKey => $dbColumn) {
            if (array_key_exists($apiKey, $measurement)) {
                $mapped[$dbColumn] = $measurement[$apiKey];
            }
        }

        return $mapped;
    }
}
