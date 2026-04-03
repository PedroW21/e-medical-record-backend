<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreateMedicalReportTemplateDTO;
use App\Modules\MedicalRecord\DTOs\UpdateMedicalReportTemplateDTO;
use App\Modules\MedicalRecord\Models\ModeloRelatorioMedico;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MedicalReportTemplateService
{
    /**
     * @return Collection<int, ModeloRelatorioMedico>
     */
    public function listForUser(int $userId): Collection
    {
        return ModeloRelatorioMedico::query()
            ->forUser($userId)
            ->orderBy('nome')
            ->get();
    }

    public function create(int $userId, CreateMedicalReportTemplateDTO $dto): ModeloRelatorioMedico
    {
        return ModeloRelatorioMedico::query()->create([
            'user_id' => $userId,
            'nome' => $dto->nome,
            'corpo_template' => $dto->corpoTemplate,
        ]);
    }

    public function update(ModeloRelatorioMedico $template, UpdateMedicalReportTemplateDTO $dto): ModeloRelatorioMedico
    {
        $data = [];

        if ($dto->nome !== null) {
            $data['nome'] = $dto->nome;
        }

        if ($dto->corpoTemplate !== null) {
            $data['corpo_template'] = $dto->corpoTemplate;
        }

        $template->update($data);

        return $template->fresh();
    }

    public function delete(ModeloRelatorioMedico $template): void
    {
        $template->delete();
    }

    public function findOrFail(int $id): ModeloRelatorioMedico
    {
        $template = ModeloRelatorioMedico::query()->find($id);

        if (! $template) {
            throw new NotFoundHttpException('Modelo de relatório médico não encontrado.');
        }

        return $template;
    }
}
