<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;

it('soft deletes an appointment', function (): void {
    $doctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor)->create();

    $response = $this->actingAs($doctor)->deleteJson("/api/appointments/{$appointment->id}");

    $response->assertOk();
    $this->assertSoftDeleted('consultas', ['id' => $appointment->id]);
});

it('does not allow deleting another doctor appointment', function (): void {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->forDoctor($doctor1)->create();

    $response = $this->actingAs($doctor2)->deleteJson("/api/appointments/{$appointment->id}");

    $response->assertNotFound();
});
