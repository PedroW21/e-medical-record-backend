<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LabCatalogService
{
    /**
     * @return LengthAwarePaginator<CatalogoExameLaboratorial>
     */
    public function listCatalog(?string $search, ?string $category, int $perPage = 15): LengthAwarePaginator
    {
        $query = CatalogoExameLaboratorial::query()->orderBy('nome');

        if ($search) {
            $query->where('nome', 'ilike', "%{$search}%");
        }

        if ($category) {
            $query->where('categoria', $category);
        }

        return $query->paginate($perPage);
    }

    public function findCatalogOrFail(string $id): CatalogoExameLaboratorial
    {
        $exam = CatalogoExameLaboratorial::query()->find($id);

        if (! $exam) {
            throw new NotFoundHttpException('Exame laboratorial não encontrado no catálogo.');
        }

        return $exam;
    }

    /**
     * @return Collection<int, PainelLaboratorial>
     */
    public function listPanels(?string $category = null): Collection
    {
        $query = PainelLaboratorial::query()->orderBy('nome');

        if ($category) {
            $query->where('categoria', $category);
        }

        return $query->get();
    }

    public function findPanelOrFail(string $id): PainelLaboratorial
    {
        $panel = PainelLaboratorial::query()->find($id);

        if (! $panel) {
            throw new NotFoundHttpException('Painel laboratorial não encontrado.');
        }

        return $panel;
    }
}
