<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Services;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Delegation\Services\DelegationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AppointmentService
{
    public function __construct(
        private readonly DelegationService $delegationService,
    ) {}

    /**
     * List appointments by date range for the authenticated user.
     *
     * @param  array{start_date: string, end_date: string, doctor_id?: int|null}  $filters
     * @return Collection<int, Consulta>
     */
    public function listByDateRange(User $user, array $filters): Collection
    {
        $requestedDoctorId = isset($filters['doctor_id']) ? (int) $filters['doctor_id'] : null;
        $doctorIds = $this->resolveDoctorIds($user, $requestedDoctorId);

        return Consulta::query()
            ->whereIn('user_id', $doctorIds)
            ->whereBetween('data', [$filters['start_date'], $filters['end_date']])
            ->with('paciente')
            ->orderBy('data')
            ->orderBy('horario')
            ->get();
    }

    /**
     * Find a single appointment, verifying access.
     */
    public function findForUser(User $user, int $appointmentId): Consulta
    {
        $doctorIds = $this->resolveDoctorIds($user);

        $appointment = Consulta::query()
            ->whereIn('user_id', $doctorIds)
            ->with('paciente')
            ->find($appointmentId);

        if (! $appointment) {
            throw new NotFoundHttpException('Consulta não encontrada.');
        }

        return $appointment;
    }

    /**
     * Check if a time slot is already occupied by a blocking appointment.
     *
     * @throws ValidationException
     */
    public function checkTimeConflict(int $doctorId, string $date, string $time, ?int $excludeId = null): void
    {
        $query = Consulta::query()
            ->where('user_id', $doctorId)
            ->where('data', $date)
            ->where('horario', $time)
            ->whereIn('status', array_map(
                fn (AppointmentStatus $s) => $s->value,
                AppointmentStatus::blockingStatuses(),
            ));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'horario' => 'Já existe uma consulta agendada para este horário.',
            ]);
        }
    }

    /**
     * Resolve which doctor IDs the user can access.
     *
     * @return list<int>
     */
    public function resolveDoctorIds(User $user, ?int $requestedDoctorId = null): array
    {
        if ($user->role === UserRole::Doctor) {
            return [$user->id];
        }

        // Secretary
        $delegatedIds = $this->delegationService->getDelegatedDoctorIds($user->id);

        if ($requestedDoctorId !== null) {
            if (! in_array($requestedDoctorId, $delegatedIds, true)) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'Você não possui delegação para este médico.',
                ]);
            }

            return [$requestedDoctorId];
        }

        return $delegatedIds;
    }

    /**
     * Resolve a single doctor ID for creating/updating appointments.
     */
    public function resolveSingleDoctorId(User $user, ?int $requestedDoctorId): int
    {
        if ($user->role === UserRole::Doctor) {
            return $user->id;
        }

        if ($requestedDoctorId === null) {
            throw ValidationException::withMessages([
                'doctor_id' => 'O campo médico é obrigatório para secretárias.',
            ]);
        }

        if (! $this->delegationService->hasDelegation($user->id, $requestedDoctorId)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Você não possui delegação para este médico.',
            ]);
        }

        return $requestedDoctorId;
    }
}
