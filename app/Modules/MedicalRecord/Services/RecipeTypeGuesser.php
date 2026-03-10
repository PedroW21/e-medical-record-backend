<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Medicamento;

final class RecipeTypeGuesser
{
    /**
     * Determine the most restrictive recipe type based on ANVISA classification.
     *
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function guess(array $itens, PrescriptionSubType $subtipo): RecipeType
    {
        if (in_array($subtipo, PrescriptionSubType::nonMedicationTypes(), true)) {
            return RecipeType::Normal;
        }

        $medicationIds = collect($itens)
            ->pluck('medication_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($medicationIds)) {
            return RecipeType::Normal;
        }

        $medications = Medicamento::query()
            ->whereIn('id', $medicationIds)
            ->whereNotNull('lista_anvisa')
            ->get();

        if ($medications->isEmpty()) {
            return RecipeType::Normal;
        }

        return $medications
            ->map(fn (Medicamento $med) => $med->lista_anvisa->requiredRecipeType())
            ->sortByDesc(fn (RecipeType $type) => $type->priority())
            ->first();
    }
}
