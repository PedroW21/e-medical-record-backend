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
     * Get availability for a doctor.
     *
     * If the doctor has schedule settings configured, returns available/occupied slots per day.
     * If not configured, falls back to returning only occupied slots.
     *
     * @group Public Schedule
     *
     * @queryParam start_date string required Start date (Y-m-d). Example: 2026-02-16
     * @queryParam end_date string required End date (Y-m-d). Example: 2026-02-28
     */
    public function availability(Request $request, string $slug): JsonResponse|AnonymousResourceCollection
    {
        $doctor = $this->findDoctorBySlug($slug);

        $startDate = $request->query('start_date', now()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->addDays(30)->format('Y-m-d'));

        $scheduleBlocks = \App\Modules\Appointment\Models\HorarioAtendimento::query()
            ->where('user_id', $doctor->id)
            ->get();

        // Fallback: no schedule configured — return only occupied slots (original behavior)
        if ($scheduleBlocks->isEmpty()) {
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

        // Enhanced: generate full slot grid
        $occupiedSlots = Consulta::query()
            ->where('user_id', $doctor->id)
            ->whereBetween('data', [$startDate, $endDate])
            ->whereIn('status', array_map(
                fn (AppointmentStatus $s) => $s->value,
                AppointmentStatus::blockingStatuses(),
            ))
            ->get()
            ->map(fn (Consulta $c) => $c->data.'|'.$c->horario)
            ->toArray();

        $schedule = [];
        $current = \Illuminate\Support\Carbon::parse($startDate);
        $end = \Illuminate\Support\Carbon::parse($endDate);

        while ($current->lte($end)) {
            $dayOfWeek = $current->dayOfWeek;

            $dayBlocks = $scheduleBlocks->filter(
                fn (\App\Modules\Appointment\Models\HorarioAtendimento $block) => $block->dia_semana->value === $dayOfWeek
            );

            if ($dayBlocks->isNotEmpty()) {
                $slots = [];
                foreach ($dayBlocks as $block) {
                    $slotTime = \Illuminate\Support\Carbon::parse($block->hora_inicio);
                    $blockEnd = \Illuminate\Support\Carbon::parse($block->hora_fim);

                    while ($slotTime->lt($blockEnd)) {
                        $timeStr = $slotTime->format('H:i');
                        $key = $current->format('Y-m-d').'|'.$timeStr;
                        $slots[] = [
                            'time' => $timeStr,
                            'available' => ! in_array($key, $occupiedSlots, true),
                        ];
                        $slotTime->addMinutes(30);
                    }
                }

                $dayEnum = \App\Modules\Appointment\Enums\DayOfWeek::from($dayOfWeek);
                $schedule[] = [
                    'date' => $current->format('Y-m-d'),
                    'day_of_week' => $dayOfWeek,
                    'day_label' => $dayEnum->label(),
                    'slots' => $slots,
                ];
            }

            $current->addDay();
        }

        return response()->json([
            'data' => [
                'slot_duration_minutes' => 30,
                'schedule' => $schedule,
            ],
        ]);
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
