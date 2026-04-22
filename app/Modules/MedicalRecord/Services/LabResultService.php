<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\LabLooseEntryDTO;
use App\Modules\MedicalRecord\DTOs\LabPanelEntryDTO;
use App\Modules\MedicalRecord\DTOs\StoreLabResultDTO;
use App\Modules\MedicalRecord\DTOs\UpdateLabValueDTO;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LabResultService
{
    public function findMedicalRecordOrFail(int $medicalRecordId): Prontuario
    {
        $prontuario = Prontuario::query()->find($medicalRecordId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    /**
     * List lab values for a medical record, with relationships needed for v2 grouping.
     *
     * @return Collection<int, ValorLaboratorial>
     */
    public function listByMedicalRecord(int $medicalRecordId): Collection
    {
        return ValorLaboratorial::query()
            ->with(['catalogoExame', 'painel'])
            ->where('prontuario_id', $medicalRecordId)
            ->orderByDesc('data_coleta')
            ->orderBy('id')
            ->get();
    }

    /**
     * Batch store lab values from v2 panel format.
     *
     * Explodes panels into individual catalog rows and stores loose entries directly.
     *
     * @return Collection<int, ValorLaboratorial>
     */
    public function batchStore(int $medicalRecordId, StoreLabResultDTO $dto): Collection
    {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        return DB::transaction(function () use ($prontuario, $dto): Collection {
            $created = new Collection;

            foreach ($dto->panels as $panelEntry) {
                $created = $created->merge(
                    $this->storePanelEntry($prontuario, $dto->date, $panelEntry, $dto->anexoId),
                );
            }

            foreach ($dto->loose as $looseEntry) {
                $created->push(
                    $this->storeLooseEntry($prontuario, $dto->date, $looseEntry, $dto->anexoId),
                );
            }

            return $created->load(['catalogoExame', 'painel']);
        });
    }

    /**
     * Store all analyte values from a single panel.
     *
     * @return Collection<int, ValorLaboratorial>
     */
    private function storePanelEntry(Prontuario $prontuario, string $date, LabPanelEntryDTO $panel, ?int $anexoId): Collection
    {
        $created = new Collection;

        $analyteIds = array_map(fn ($v) => $v->analyteId, $panel->values);
        $catalogItems = CatalogoExameLaboratorial::query()
            ->whereIn('id', $analyteIds)
            ->get()
            ->keyBy('id');

        foreach ($panel->values as $value) {
            $catalog = $catalogItems->get($value->analyteId);
            $numericValue = $this->extractNumericValue($value->value);

            $labValue = ValorLaboratorial::query()->create([
                'prontuario_id' => $prontuario->id,
                'paciente_id' => $prontuario->paciente_id,
                'catalogo_exame_id' => $value->analyteId,
                'nome_avulso' => null,
                'data_coleta' => $date,
                'valor' => $value->value,
                'valor_numerico' => $numericValue,
                'unidade' => $catalog?->unidade ?? '',
                'faixa_referencia' => $catalog?->faixa_referencia,
                'painel_id' => $panel->panelId,
                'anexo_id' => $anexoId,
            ]);

            $created->push($labValue);
        }

        return $created;
    }

    /**
     * Store a single loose (free-form) lab entry.
     */
    private function storeLooseEntry(Prontuario $prontuario, string $date, LabLooseEntryDTO $entry, ?int $anexoId): ValorLaboratorial
    {
        $numericValue = $this->extractNumericValue($entry->value);

        return ValorLaboratorial::query()->create([
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'catalogo_exame_id' => null,
            'nome_avulso' => $entry->name,
            'data_coleta' => $date,
            'valor' => $entry->value,
            'valor_numerico' => $numericValue,
            'unidade' => $entry->unit,
            'faixa_referencia' => $entry->referenceRange,
            'painel_id' => null,
            'anexo_id' => $anexoId,
        ]);
    }

    public function update(int $labValueId, UpdateLabValueDTO $dto): ValorLaboratorial
    {
        $labValue = $this->findOrFail($labValueId);
        $this->ensureDraft($labValue->prontuario);

        $data = [];

        if ($dto->value !== null) {
            $data['valor'] = $dto->value;
            $data['valor_numerico'] = $this->extractNumericValue($dto->value);
        }

        if ($dto->unit !== null) {
            $data['unidade'] = $dto->unit;
        }

        if ($dto->hasReferenceRange) {
            $data['faixa_referencia'] = $dto->referenceRange;
        }

        if ($dto->collectionDate !== null) {
            $data['data_coleta'] = $dto->collectionDate;
        }

        if ($dto->hasAnexoId) {
            $data['anexo_id'] = $dto->anexoId;
        }

        $labValue->update($data);

        return $labValue->fresh()->load('catalogoExame');
    }

    public function delete(int $labValueId): void
    {
        $labValue = $this->findOrFail($labValueId);
        $this->ensureDraft($labValue->prontuario);

        $labValue->delete();
    }

    /**
     * Replace all lab-analyte rows for this attachment with a fresh batch from `payload`.
     *
     * Idempotent: any previously materialised rows for this anexo are deleted before
     * the new ones are created, covering the re-confirm scenario. If the payload has
     * no `panels` or `loose`, no rows are created — but the purge still happens.
     *
     * @param  array<string, mixed>  $payload  Frontend-shape lab payload (date, panels[], loose[]).
     * @return Collection<int, ValorLaboratorial>
     *
     * @throws NotFoundHttpException When the medical record is not found
     */
    public function materializeFromAttachment(
        int $medicalRecordId,
        int $anexoId,
        array $payload,
    ): Collection {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);

        return DB::transaction(function () use ($prontuario, $anexoId, $payload): Collection {
            ValorLaboratorial::query()->where('anexo_id', $anexoId)->delete();

            $panels = array_map(
                fn (array $p): LabPanelEntryDTO => LabPanelEntryDTO::fromArray($p),
                $payload['panels'] ?? [],
            );

            $loose = array_map(
                fn (array $l): LabLooseEntryDTO => LabLooseEntryDTO::fromArray($l),
                $payload['loose'] ?? [],
            );

            $created = new Collection;

            foreach ($panels as $panelEntry) {
                $created = $created->merge(
                    $this->storePanelEntry($prontuario, $payload['date'] ?? now()->toDateString(), $panelEntry, $anexoId),
                );
            }

            foreach ($loose as $looseEntry) {
                $created->push(
                    $this->storeLooseEntry($prontuario, $payload['date'] ?? now()->toDateString(), $looseEntry, $anexoId),
                );
            }

            return $created->load(['catalogoExame', 'painel']);
        });
    }

    public function findOrFail(int $labValueId): ValorLaboratorial
    {
        $labValue = ValorLaboratorial::query()->with('prontuario')->find($labValueId);

        if (! $labValue) {
            throw new NotFoundHttpException('Valor laboratorial não encontrado.');
        }

        return $labValue;
    }

    public function findForMedicalRecordOrFail(int $labValueId, int $medicalRecordId): ValorLaboratorial
    {
        $labValue = $this->findOrFail($labValueId);

        if ($labValue->prontuario_id !== $medicalRecordId) {
            throw new NotFoundHttpException('Valor laboratorial não encontrado.');
        }

        return $labValue;
    }

    /**
     * Extract numeric value from string result (e.g. "14.5" -> 14.5, "Negativo" -> null).
     */
    private function extractNumericValue(string $value): ?float
    {
        $normalized = str_replace(',', '.', trim($value));

        if (is_numeric($normalized)) {
            return (float) $normalized;
        }

        return null;
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar resultados laboratoriais de um prontuário finalizado.');
        }
    }
}
