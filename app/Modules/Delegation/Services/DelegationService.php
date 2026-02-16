<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Services;

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;
use Illuminate\Database\Eloquent\Collection;

final class DelegationService
{
    /**
     * List delegations for a user (as doctor or secretary).
     *
     * @return Collection<int, Delegacao>
     */
    public function listForUser(User $user): Collection
    {
        return Delegacao::query()
            ->where('medico_id', $user->id)
            ->orWhere('secretaria_id', $user->id)
            ->with(['medico', 'secretaria'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the doctor IDs a secretary has delegation for.
     *
     * @return list<int>
     */
    public function getDelegatedDoctorIds(int $secretaryId): array
    {
        return Delegacao::query()
            ->where('secretaria_id', $secretaryId)
            ->pluck('medico_id')
            ->all();
    }

    /**
     * Check if a secretary has delegation for a specific doctor.
     */
    public function hasDelegation(int $secretaryId, int $doctorId): bool
    {
        return Delegacao::query()
            ->where('secretaria_id', $secretaryId)
            ->where('medico_id', $doctorId)
            ->exists();
    }
}
