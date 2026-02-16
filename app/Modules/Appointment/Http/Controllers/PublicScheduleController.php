<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Controllers;

use App\Models\User;
use App\Modules\Appointment\Actions\BookPublicAppointmentAction;
use App\Modules\Appointment\DTOs\BookPublicAppointmentDTO;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Http\Requests\BookPublicAppointmentRequest;
use App\Modules\Appointment\Http\Resources\AppointmentResource;
use App\Modules\Appointment\Http\Resources\AvailabilityResource;
use App\Modules\Appointment\Models\Consulta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicScheduleController
{
    public function __construct(
        private readonly BookPublicAppointmentAction $bookAction,
    ) {}

    /**
     * Get occupied time slots for a doctor.
     *
     * @group Public Schedule
     *
     * @queryParam start_date string required Start date (Y-m-d). Example: 2026-02-16
     * @queryParam end_date string required End date (Y-m-d). Example: 2026-02-28
     */
    public function availability(Request $request, string $slug): AnonymousResourceCollection
    {
        $doctor = $this->findDoctorBySlug($slug);

        $startDate = $request->query('start_date', now()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->addDays(30)->format('Y-m-d'));

        $occupiedSlots = Consulta::query()
            ->where('user_id', $doctor->id)
            ->whereBetween('data', [$startDate, $endDate])
            ->whereIn('status', array_map(
                fn (AppointmentStatus $s) => $s->value,
                AppointmentStatus::blockingStatuses(),
            ))
            ->orderBy('data')
            ->orderBy('horario')
            ->get();

        return AvailabilityResource::collection($occupiedSlots);
    }

    /**
     * Book a public appointment request.
     *
     * @group Public Schedule
     */
    public function book(BookPublicAppointmentRequest $request, string $slug): JsonResponse
    {
        $doctor = $this->findDoctorBySlug($slug);

        $dto = BookPublicAppointmentDTO::fromRequest($request);
        $appointment = $this->bookAction->execute(
            doctorId: $doctor->id,
            dto: $dto,
        );

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    private function findDoctorBySlug(string $slug): User
    {
        $doctor = User::query()->where('slug', $slug)->first();

        if (! $doctor) {
            throw new NotFoundHttpException('Médico não encontrado.');
        }

        return $doctor;
    }
}
