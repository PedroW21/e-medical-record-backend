<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();
});

it('lists notifications for the authenticated user', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'type', 'payload', 'read_at', 'created_at']],
            'meta',
        ]);
});

it('filters notifications by unread status', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $this->doctor->notifications()->first()->markAsRead();

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications?status=unread');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters notifications by read status', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));
    $this->doctor->notifications()->first()->markAsRead();

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications?status=read');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns unread count', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications/unread-count');

    $response->assertOk()
        ->assertJsonPath('data.count', 1);
});

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/notifications')->assertUnauthorized();
});
