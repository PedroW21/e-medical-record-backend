<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreateExamRequestModelDTO;
use App\Modules\MedicalRecord\DTOs\UpdateExamRequestModelDTO;
use App\Modules\MedicalRecord\Models\ModeloSolicitacaoExame;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExamRequestModelService
{
    /**
     * @return Collection<int, ModeloSolicitacaoExame>
     */
    public function listForUser(int $userId, ?string $category = null): Collection
    {
        $query = ModeloSolicitacaoExame::query()->forUser($userId);

        if ($category !== null) {
            $query->where('categoria', $category);
        }

        return $query->orderBy('nome')->get();
    }

    public function create(int $userId, CreateExamRequestModelDTO $dto): ModeloSolicitacaoExame
    {
        return ModeloSolicitacaoExame::query()->create([
            'user_id' => $userId,
            'nome' => $dto->nome,
            'categoria' => $dto->categoria,
            'itens' => $dto->itens,
        ]);
    }

    public function update(ModeloSolicitacaoExame $model, UpdateExamRequestModelDTO $dto): ModeloSolicitacaoExame
    {
        $data = [];

        if ($dto->nome !== null) {
            $data['nome'] = $dto->nome;
        }

        if ($dto->categoria !== null) {
            $data['categoria'] = $dto->categoria;
        }

        if ($dto->itens !== null) {
            $data['itens'] = $dto->itens;
        }

        $model->update($data);

        return $model->fresh();
    }

    public function delete(ModeloSolicitacaoExame $model): void
    {
        $model->delete();
    }

    public function findOrFail(int $id): ModeloSolicitacaoExame
    {
        $model = ModeloSolicitacaoExame::query()->find($id);

        if (! $model) {
            throw new NotFoundHttpException('Modelo de solicitação de exame não encontrado.');
        }

        return $model;
    }
}
