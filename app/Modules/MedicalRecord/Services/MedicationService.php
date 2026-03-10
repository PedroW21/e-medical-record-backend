<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\MedicationFilterDTO;
use App\Modules\MedicalRecord\Models\Medicamento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MedicationService
{
    /**
     * @return LengthAwarePaginator<Medicamento>
     */
    public function list(MedicationFilterDTO $filters): LengthAwarePaginator
    {
        $query = Medicamento::query()->ativo();

        if ($filters->search) {
            $search = mb_strtolower($filters->search);
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(nome) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(principio_ativo) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($filters->controlado !== null) {
            $filters->controlado
                ? $query->controlado()
                : $query->whereNull('lista_anvisa');
        }

        return $query
            ->orderBy('nome')
            ->paginate(perPage: $filters->perPage);
    }

    public function findOrFail(int $id): Medicamento
    {
        $medication = Medicamento::query()->ativo()->find($id);

        if (! $medication) {
            throw new NotFoundHttpException('Medicamento não encontrado.');
        }

        return $medication;
    }
}
