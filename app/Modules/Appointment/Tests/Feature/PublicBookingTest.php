<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;
use App\Modules\Delegation\Models\Delegacao;
use Illuminate\Support\Facades\Notification;

it('creates a public booking request', function (): void {
    Notification::fake();
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-booking']);

    $response = $this->postJson('/api/public/schedule/dr-booking/book', [
        'nome' => 'Maria Silva',
        'telefone' => '(11) 98765-4321',
        'email' => 'maria@example.com',
        'data' => now()->addDay()->format('Y-m-d'),
        'horario' => '14:00',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'requested')
        ->assertJsonPath('data.requester_name', 'Maria Silva');
});

it('sends notification to doctor and secretaries on public booking', function (): void {
    Notification::fake();

    $doctor = User::factory()->doctor()->create(['slug' => 'dr-notify']);
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $this->postJson('/api/public/schedule/dr-notify/book', [
        'nome' => 'João Souza',
        'telefone' => '(11) 91234-5678',
        'email' => 'joao@example.com',
        'data' => now()->addDay()->format('Y-m-d'),
        'horario' => '15:00',
    ]);

    Notification::assertSentTo($doctor, NewPublicAppointmentRequested::class);
    Notification::assertSentTo($secretary, NewPublicAppointmentRequested::class);
});

it('rejects public booking without required fields', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-reject']);

    $response = $this->postJson('/api/public/schedule/dr-reject/book', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['nome', 'telefone', 'email', 'data', 'horario']);
});
