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
     * Check if the requested time is within the doctor's working hours.
     *
     * If the doctor has no schedule configured, validation is skipped (no restrictions).
     *
     * @throws ValidationException
     */
    public function checkWorkingHours(int $doctorId, string $date, string $time): void
    {
        $allBlocks = \App\Modules\Appointment\Models\HorarioAtendimento::query()
            ->where('user_id', $doctorId)
            ->get();

        // No schedule configured — skip validation
        if ($allBlocks->isEmpty()) {
            return;
        }

        $dayOfWeek = (int) \Illuminate\Support\Carbon::parse($date)->dayOfWeek;

        $dayBlocks = $allBlocks->filter(
            fn (\App\Modules\Appointment\Models\HorarioAtendimento $block) => $block->dia_semana->value === $dayOfWeek
        );

        $covered = $dayBlocks->contains(
            fn (\App\Modules\Appointment\Models\HorarioAtendimento $block) => $time >= $block->hora_inicio && $time < $block->hora_fim
        );

        if (! $covered) {
            throw ValidationException::withMessages([
                'horario' => 'Este horário está fora da janela de atendimento do médico.',
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

    /**
     * Get all schedule settings blocks for a doctor.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Appointment\Models\HorarioAtendimento>
     */
    public function getScheduleSettings(int $doctorId): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Modules\Appointment\Models\HorarioAtendimento::query()
            ->where('user_id', $doctorId)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Replace all schedule settings blocks for a doctor.
     *
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $blocks
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Appointment\Models\HorarioAtendimento>
     *
     * @throws ValidationException
     */
    public function replaceScheduleSettings(int $doctorId, array $blocks): \Illuminate\Database\Eloquent\Collection
    {
        $this->validateNoOverlappingBlocks($blocks);

        \Illuminate\Support\Facades\DB::transaction(function () use ($doctorId, $blocks): void {
            \App\Modules\Appointment\Models\HorarioAtendimento::query()
                ->where('user_id', $doctorId)
                ->delete();

            foreach ($blocks as $block) {
                \App\Modules\Appointment\Models\HorarioAtendimento::query()->create([
                    'user_id' => $doctorId,
                    'dia_semana' => $block['day_of_week'],
                    'hora_inicio' => $block['start_time'],
                    'hora_fim' => $block['end_time'],
                ]);
            }
        });

        return $this->getScheduleSettings($doctorId);
    }

    /**
     * Validate that no blocks overlap on the same day.
     *
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $blocks
     *
     * @throws ValidationException
     */
    private function validateNoOverlappingBlocks(array $blocks): void
    {
        $groupedByDay = [];
        foreach ($blocks as $block) {
            $groupedByDay[$block['day_of_week']][] = $block;
        }

        foreach ($groupedByDay as $dayBlocks) {
            $count = count($dayBlocks);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($dayBlocks[$i]['start_time'] < $dayBlocks[$j]['end_time']
                        && $dayBlocks[$j]['start_time'] < $dayBlocks[$i]['end_time']) {
                        throw ValidationException::withMessages([
                            'blocks' => 'Existem blocos de horário sobrepostos no mesmo dia.',
                        ]);
                    }
                }
            }
        }
    }
}
