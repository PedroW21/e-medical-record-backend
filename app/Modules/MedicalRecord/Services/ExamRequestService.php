<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreateExamRequestDTO;
use App\Modules\MedicalRecord\DTOs\UpdateExamRequestDTO;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExamRequestService
{
    /**
     * @return Collection<int, SolicitacaoExame>
     */
    public function listForRecord(int $prontuarioId): Collection
    {
        return SolicitacaoExame::query()
            ->where('prontuario_id', $prontuarioId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(int $prontuarioId, CreateExamRequestDTO $dto): SolicitacaoExame
    {
        $prontuario = $this->findMedicalRecordOrFail($prontuarioId);
        $this->ensureDraft($prontuario);

        return SolicitacaoExame::query()->create([
            'prontuario_id' => $prontuarioId,
            'modelo_id' => $dto->modeloId,
            'cid_10' => $dto->cid10,
            'indicacao_clinica' => $dto->indicacaoClinica,
            'itens' => $dto->itens,
            'relatorio_medico' => $dto->relatorioMedico,
        ]);
    }

    public function update(SolicitacaoExame $examRequest, UpdateExamRequestDTO $dto): SolicitacaoExame
    {
        $prontuario = $examRequest->relationLoaded('prontuario')
            ? $examRequest->prontuario
            : $examRequest->prontuario()->firstOrFail();

        $this->ensureDraft($prontuario);

        $data = [];

        if ($dto->modeloId !== null) {
            $data['modelo_id'] = $dto->modeloId;
        }

        if ($dto->cid10 !== null) {
            $data['cid_10'] = $dto->cid10;
        }

        if ($dto->indicacaoClinica !== null) {
            $data['indicacao_clinica'] = $dto->indicacaoClinica;
        }

        if ($dto->itens !== null) {
            $data['itens'] = $dto->itens;
        }

        if ($dto->relatorioMedico !== null) {
            $data['relatorio_medico'] = $dto->relatorioMedico;
        }

        $examRequest->update($data);

        return $examRequest->fresh();
    }

    public function delete(SolicitacaoExame $examRequest): void
    {
        $prontuario = $examRequest->relationLoaded('prontuario')
            ? $examRequest->prontuario
            : $examRequest->prontuario()->firstOrFail();

        $this->ensureDraft($prontuario);

        $examRequest->delete();
    }

    public function markAsPrinted(SolicitacaoExame $examRequest): SolicitacaoExame
    {
        $examRequest->update(['impresso_em' => now()]);

        return $examRequest->fresh();
    }

    public function findOrFail(int $id): SolicitacaoExame
    {
        $examRequest = SolicitacaoExame::query()->with('prontuario')->find($id);

        if (! $examRequest) {
            throw new NotFoundHttpException('Solicitação de exame não encontrada.');
        }

        return $examRequest;
    }

    public function findForMedicalRecordOrFail(int $id, int $prontuarioId): SolicitacaoExame
    {
        $examRequest = $this->findOrFail($id);

        if ($examRequest->prontuario_id !== $prontuarioId) {
            throw new NotFoundHttpException('Solicitação de exame não encontrada.');
        }

        return $examRequest;
    }

    public function findMedicalRecordOrFail(int $prontuarioId): Prontuario
    {
        $prontuario = Prontuario::query()->find($prontuarioId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar solicitações de exame de um prontuário finalizado.');
        }
    }
}
