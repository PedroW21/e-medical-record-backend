<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();
});

it('soft deletes a notification', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $notificationId = $this->doctor->notifications()->first()->id;

    $response = $this->actingAs($this->doctor)->deleteJson("/api/notifications/{$notificationId}");

    $response->assertOk()
        ->assertJsonPath('message', 'Notificação excluída com sucesso.');

    $this->assertDatabaseHas('notifications', [
        'id' => $notificationId,
    ]);

    $this->actingAs($this->doctor)->getJson('/api/notifications')
        ->assertJsonCount(0, 'data');
});

it('returns 404 when deleting nonexistent notification', function (): void {
    $response = $this->actingAs($this->doctor)->deleteJson('/api/notifications/nonexistent-uuid');

    $response->assertNotFound();
});

it('cannot delete another users notification', function (): void {
    $otherDoctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->requested()->forDoctor($otherDoctor)->create();
    $otherDoctor->notify(new NewPublicAppointmentRequested($appointment));

    $notificationId = $otherDoctor->notifications()->first()->id;

    $response = $this->actingAs($this->doctor)->deleteJson("/api/notifications/{$notificationId}");

    $response->assertNotFound();
});
