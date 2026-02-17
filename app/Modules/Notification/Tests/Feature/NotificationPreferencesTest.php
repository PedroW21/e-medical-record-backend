<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Notification\Models\PreferenciaNotificacao;
use App\Modules\Notification\NotificationTypeRegistry;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();

    NotificationTypeRegistry::flush();
    NotificationTypeRegistry::register(
        slug: 'new_public_appointment_requested',
        label: 'Nova solicitação de agendamento',
        channels: ['database', 'mail', 'broadcast'],
    );
});

it('lists notification preferences with defaults', function (): void {
    $response = $this->actingAs($this->doctor)->getJson('/api/notifications/preferences');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'new_public_appointment_requested')
        ->assertJsonPath('data.0.channels.database.enabled', true)
        ->assertJsonPath('data.0.channels.database.locked', true)
        ->assertJsonPath('data.0.channels.mail.enabled', true)
        ->assertJsonPath('data.0.channels.mail.locked', false);
});

it('updates notification preferences', function (): void {
    $response = $this->actingAs($this->doctor)->putJson('/api/notifications/preferences', [
        'preferences' => [
            ['type' => 'new_public_appointment_requested', 'channel' => 'mail', 'enabled' => false],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('preferencias_notificacao', [
        'user_id' => $this->doctor->id,
        'tipo_notificacao' => 'new_public_appointment_requested',
        'canal' => 'mail',
        'ativo' => false,
    ]);
});

it('ignores database channel updates', function (): void {
    $response = $this->actingAs($this->doctor)->putJson('/api/notifications/preferences', [
        'preferences' => [
            ['type' => 'new_public_appointment_requested', 'channel' => 'database', 'enabled' => false],
        ],
    ]);

    $response->assertUnprocessable();
});

it('reflects updated preferences in listing', function (): void {
    PreferenciaNotificacao::query()->create([
        'user_id' => $this->doctor->id,
        'tipo_notificacao' => 'new_public_appointment_requested',
        'canal' => 'mail',
        'ativo' => false,
    ]);

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications/preferences');

    $response->assertOk()
        ->assertJsonPath('data.0.channels.mail.enabled', false);
});
