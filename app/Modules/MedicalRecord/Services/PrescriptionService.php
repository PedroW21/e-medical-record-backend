<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreatePrescriptionDTO;
use App\Modules\MedicalRecord\DTOs\UpdatePrescriptionDTO;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PrescriptionService
{
    public function __construct(
        private readonly RecipeTypeGuesser $recipeTypeGuesser,
    ) {}

    /**
     * @return Collection<int, Prescricao>
     */
    public function listByMedicalRecord(int $medicalRecordId): Collection
    {
        $this->findMedicalRecordOrFail($medicalRecordId);

        return Prescricao::query()
            ->where('prontuario_id', $medicalRecordId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(int $medicalRecordId, CreatePrescriptionDTO $dto): Prescricao
    {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        $tipoReceita = $this->resolveRecipeType($dto->itens, $dto->subtipo, $dto->tipoReceitaOverride, $dto->tipoReceitaManual);

        return Prescricao::query()->create([
            'prontuario_id' => $medicalRecordId,
            'subtipo' => $dto->subtipo,
            'tipo_receita' => $tipoReceita,
            'tipo_receita_override' => $dto->tipoReceitaOverride,
            'itens' => $dto->itens,
            'observacoes' => $dto->observacoes,
        ]);
    }

    public function update(int $prescriptionId, UpdatePrescriptionDTO $dto): Prescricao
    {
        $prescription = $this->findOrFail($prescriptionId);
        $this->ensureDraft($prescription->prontuario);

        $data = array_filter([
            'subtipo' => $dto->subtipo,
            'itens' => $dto->itens,
            'observacoes' => $dto->observacoes,
            'tipo_receita_override' => $dto->tipoReceitaOverride,
        ], fn ($value) => $value !== null);

        $itens = $dto->itens ?? $prescription->itens;
        $subtipo = $dto->subtipo ?? $prescription->subtipo;
        $override = $dto->tipoReceitaOverride ?? $prescription->tipo_receita_override;
        $manual = $dto->tipoReceitaManual;

        $data['tipo_receita'] = $this->resolveRecipeType($itens, $subtipo, $override, $manual);

        $prescription->update($data);

        return $prescription->fresh();
    }

    public function delete(int $prescriptionId): void
    {
        $prescription = $this->findOrFail($prescriptionId);
        $this->ensureDraft($prescription->prontuario);

        $prescription->delete();
    }

    public function findOrFail(int $prescriptionId): Prescricao
    {
        $prescription = Prescricao::query()->with('prontuario')->find($prescriptionId);

        if (! $prescription) {
            throw new NotFoundHttpException('Prescrição não encontrada.');
        }

        return $prescription;
    }

    public function findMedicalRecordOrFail(int $medicalRecordId): Prontuario
    {
        $prontuario = Prontuario::query()->find($medicalRecordId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar prescrições de um prontuário finalizado.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function resolveRecipeType(
        array $itens,
        PrescriptionSubType $subtipo,
        bool $override,
        ?string $manual,
    ): RecipeType {
        if ($override && $manual) {
            return RecipeType::from($manual);
        }

        return $this->recipeTypeGuesser->guess($itens, $subtipo);
    }
}
