<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreatePrescriptionTemplateDTO;
use App\Modules\MedicalRecord\DTOs\UpdatePrescriptionTemplateDTO;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PrescriptionTemplateService
{
    /**
     * @return Collection<int, ModeloPrescricao>
     */
    public function listForUser(int $userId, ?PrescriptionSubType $subtipo = null): Collection
    {
        $query = ModeloPrescricao::query()->forUser($userId);

        if ($subtipo) {
            $query->where('subtipo', $subtipo);
        }

        return $query->orderBy('nome')->get();
    }

    public function create(int $userId, CreatePrescriptionTemplateDTO $dto): ModeloPrescricao
    {
        return ModeloPrescricao::query()->create([
            'user_id' => $userId,
            'nome' => $dto->nome,
            'subtipo' => $dto->subtipo,
            'itens' => $dto->itens,
            'tags' => $dto->tags,
        ]);
    }

    public function update(int $templateId, UpdatePrescriptionTemplateDTO $dto): ModeloPrescricao
    {
        $template = $this->findOrFail($templateId);

        $data = array_filter([
            'nome' => $dto->nome,
            'itens' => $dto->itens,
            'tags' => $dto->tags,
        ], fn ($value) => $value !== null);

        $template->update($data);

        return $template->fresh();
    }

    public function delete(int $templateId): void
    {
        $template = $this->findOrFail($templateId);
        $template->delete();
    }

    public function findOrFail(int $templateId): ModeloPrescricao
    {
        $template = ModeloPrescricao::query()->find($templateId);

        if (! $template) {
            throw new NotFoundHttpException('Modelo de prescrição não encontrado.');
        }

        return $template;
    }
}
