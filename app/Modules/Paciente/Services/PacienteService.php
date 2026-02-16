<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Services;

use App\Modules\Paciente\Models\Paciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PacienteService
{
    /**
     * @param  array{busca?: string|null, status?: string|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<Paciente>
     */
    public function listForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Paciente::query()
            ->where('user_id', $userId)
            ->with(['alergias', 'condicoesCronicas']);

        if (! empty($filters['busca'])) {
            $busca = $filters['busca'];
            $query->where(function ($q) use ($busca): void {
                $q->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%'])
                    ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(perPage: (int) ($filters['per_page'] ?? 15));
    }

    public function findForUser(int $userId, int $pacienteId): Paciente
    {
        $paciente = Paciente::query()
            ->where('user_id', $userId)
            ->with(['endereco', 'alergias', 'condicoesCronicas'])
            ->find($pacienteId);

        if (! $paciente) {
            throw new NotFoundHttpException('Paciente não encontrado.');
        }

        return $paciente;
    }
}
