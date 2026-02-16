<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Policies;

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class AppointmentPolicy
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Consulta $appointment): bool
    {
        return $this->hasAccess($user, $appointment);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Consulta $appointment): bool
    {
        return $this->hasAccess($user, $appointment);
    }

    public function delete(User $user, Consulta $appointment): bool
    {
        return $this->hasAccess($user, $appointment);
    }

    private function hasAccess(User $user, Consulta $appointment): bool
    {
        $doctorIds = $this->appointmentService->resolveDoctorIds($user);

        return in_array($appointment->user_id, $doctorIds, true);
    }
}
