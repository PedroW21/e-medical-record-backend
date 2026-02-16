<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Listeners;

use App\Models\User;
use App\Modules\Appointment\Events\PublicAppointmentRequested;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;
use App\Modules\Delegation\Services\DelegationService;

final class SendNewAppointmentNotification
{
    public function __construct(
        private readonly DelegationService $delegationService,
    ) {}

    public function handle(PublicAppointmentRequested $event): void
    {
        $appointment = $event->appointment;
        $doctor = User::query()->find($appointment->user_id);

        if (! $doctor) {
            return;
        }

        // Notify the doctor
        $doctor->notify(new NewPublicAppointmentRequested($appointment));

        // Notify all delegated secretaries
        $secretaryIds = $this->delegationService->getDelegatedDoctorIds($doctor->id);

        // getDelegatedDoctorIds returns doctor IDs for a secretary, but we need the reverse.
        // Get secretaries who have delegation for this doctor.
        $secretaries = User::query()
            ->whereIn('id', function ($query) use ($doctor): void {
                $query->select('secretaria_id')
                    ->from('delegacoes')
                    ->where('medico_id', $doctor->id);
            })
            ->get();

        foreach ($secretaries as $secretary) {
            $secretary->notify(new NewPublicAppointmentRequested($appointment));
        }
    }
}
