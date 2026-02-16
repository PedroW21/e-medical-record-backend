<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Controllers;

use App\Modules\Appointment\Http\Requests\UpdateScheduleSettingsRequest;
use App\Modules\Appointment\Http\Resources\ScheduleSettingsResource;
use App\Modules\Appointment\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage doctor schedule settings (working hours).
 *
 * @group Schedule Settings
 *
 * @authenticated
 */
final class ScheduleSettingsController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    /**
     * List working hours for the authenticated doctor.
     *
     * @queryParam doctor_id int Optional doctor ID (for secretaries). Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $requestedDoctorId = $request->query('doctor_id') !== null
            ? (int) $request->query('doctor_id')
            : null;

        $doctorId = $this->appointmentService->resolveSingleDoctorId($user, $requestedDoctorId);

        $blocks = $this->appointmentService->getScheduleSettings($doctorId);

        return response()->json([
            'data' => [
                'slot_duration_minutes' => 30,
                'blocks' => ScheduleSettingsResource::collection($blocks),
            ],
        ]);
    }

    /**
     * Replace all working hours for a doctor.
     */
    public function update(UpdateScheduleSettingsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $requestedDoctorId = $request->validated('doctor_id') !== null
            ? (int) $request->validated('doctor_id')
            : null;

        $doctorId = $this->appointmentService->resolveSingleDoctorId($user, $requestedDoctorId);

        $blocks = $this->appointmentService->replaceScheduleSettings(
            $doctorId,
            $request->validated('blocks'),
        );

        return response()->json([
            'data' => [
                'slot_duration_minutes' => 30,
                'blocks' => ScheduleSettingsResource::collection($blocks),
            ],
        ]);
    }
}
