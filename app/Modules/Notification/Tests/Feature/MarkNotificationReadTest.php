<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();
});

it('marks a notification as read', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $notificationId = $this->doctor->notifications()->first()->id;

    $response = $this->actingAs($this->doctor)->patchJson("/api/notifications/{$notificationId}/read");

    $response->assertOk()
        ->assertJsonPath('data.read_at', fn ($value) => $value !== null);
});

it('marks all notifications as read', function (): void {
    $appointment1 = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $appointment2 = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment1));
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment2));

    $response = $this->actingAs($this->doctor)->patchJson('/api/notifications/read-all');

    $response->assertOk()
        ->assertJsonPath('data.count', 2);

    expect($this->doctor->unreadNotifications()->count())->toBe(0);
});

it('returns 404 for nonexistent notification', function (): void {
    $response = $this->actingAs($this->doctor)->patchJson('/api/notifications/nonexistent-uuid/read');

    $response->assertNotFound();
});
