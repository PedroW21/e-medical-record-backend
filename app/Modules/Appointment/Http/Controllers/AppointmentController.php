<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Controllers;

use App\Modules\Appointment\Actions\CreateAppointmentAction;
use App\Modules\Appointment\Actions\DeleteAppointmentAction;
use App\Modules\Appointment\Actions\UpdateAppointmentAction;
use App\Modules\Appointment\Actions\UpdateAppointmentStatusAction;
use App\Modules\Appointment\DTOs\CreateAppointmentDTO;
use App\Modules\Appointment\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Http\Requests\ListAppointmentRequest;
use App\Modules\Appointment\Http\Requests\StoreAppointmentRequest;
use App\Modules\Appointment\Http\Requests\UpdateAppointmentRequest;
use App\Modules\Appointment\Http\Requests\UpdateAppointmentStatusRequest;
use App\Modules\Appointment\Http\Resources\AppointmentResource;
use App\Modules\Appointment\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class AppointmentController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly CreateAppointmentAction $createAction,
        private readonly UpdateAppointmentAction $updateAction,
        private readonly DeleteAppointmentAction $deleteAction,
        private readonly UpdateAppointmentStatusAction $updateStatusAction,
    ) {}

    /**
     * List appointments by date range.
     *
     * @authenticated
     *
     * @group Appointments
     *
     * @queryParam start_date string required Start date (Y-m-d). Example: 2026-02-16
     * @queryParam end_date string required End date (Y-m-d). Example: 2026-02-28
     * @queryParam doctor_id int Optional doctor ID (for secretaries). Example: 1
     */
    public function index(ListAppointmentRequest $request): AnonymousResourceCollection
    {
        $appointments = $this->appointmentService->listByDateRange(
            user: $request->user(),
            filters: $request->validated(),
        );

        return AppointmentResource::collection($appointments);
    }

    /**
     * Create a new appointment.
     *
     * @authenticated
     *
     * @group Appointments
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $dto = CreateAppointmentDTO::fromRequest($request);
        $doctorId = $this->appointmentService->resolveSingleDoctorId(
            user: $request->user(),
            requestedDoctorId: $dto->doctorId,
        );

        $appointment = $this->createAction->execute(
            doctorId: $doctorId,
            dto: $dto,
        );

        $appointment->load('paciente');

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single appointment.
     *
     * @authenticated
     *
     * @group Appointments
     */
    public function show(int $id, Request $request): AppointmentResource
    {
        $appointment = $this->appointmentService->findForUser(
            user: $request->user(),
            appointmentId: $id,
        );

        return new AppointmentResource($appointment);
    }

    /**
     * Update an appointment.
     *
     * @authenticated
     *
     * @group Appointments
     */
    public function update(int $id, UpdateAppointmentRequest $request): AppointmentResource
    {
        $appointment = $this->appointmentService->findForUser(
            user: $request->user(),
            appointmentId: $id,
        );

        Gate::authorize('update', $appointment);

        $dto = UpdateAppointmentDTO::fromRequest($request);
        $updated = $this->updateAction->execute(
            appointment: $appointment,
            dto: $dto,
        );

        $updated->load('paciente');

        return new AppointmentResource($updated);
    }

    /**
     * Update appointment status.
     *
     * @authenticated
     *
     * @group Appointments
     */
    public function updateStatus(int $id, UpdateAppointmentStatusRequest $request): AppointmentResource
    {
        $appointment = $this->appointmentService->findForUser(
            user: $request->user(),
            appointmentId: $id,
        );

        Gate::authorize('update', $appointment);

        $newStatus = AppointmentStatus::from($request->validated('status'));
        $updated = $this->updateStatusAction->execute(
            appointment: $appointment,
            newStatus: $newStatus,
        );

        return new AppointmentResource($updated);
    }

    /**
     * Delete an appointment (soft delete).
     *
     * @authenticated
     *
     * @group Appointments
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $appointment = $this->appointmentService->findForUser(
            user: $request->user(),
            appointmentId: $id,
        );

        Gate::authorize('delete', $appointment);

        $this->deleteAction->execute(appointment: $appointment);

        return response()->json(['message' => 'Consulta excluída com sucesso.']);
    }

    /**
     * List all appointment types.
     *
     * @authenticated
     *
     * @group Appointments
     */
    public function types(): JsonResponse
    {
        $types = array_map(
            fn (AppointmentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            AppointmentType::cases(),
        );

        return response()->json(['data' => $types]);
    }
}
