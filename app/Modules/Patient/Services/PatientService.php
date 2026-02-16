<?php

declare(strict_types=1);

namespace App\Modules\Patient\Services;

use App\Modules\Patient\Models\Paciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PatientService
{
    /**
     * @param  array{search?: string|null, status?: string|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<Paciente>
     */
    public function listForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Paciente::query()
            ->where('user_id', $userId)
            ->with(['alergias', 'condicoesCronicas']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(perPage: (int) ($filters['per_page'] ?? 15));
    }

    public function findForUser(int $userId, int $patientId): Paciente
    {
        $patient = Paciente::query()
            ->where('user_id', $userId)
            ->with(['endereco', 'alergias', 'condicoesCronicas'])
            ->find($patientId);

        if (! $patient) {
            throw new NotFoundHttpException('Paciente não encontrado.');
        }

        return $patient;
    }
}
