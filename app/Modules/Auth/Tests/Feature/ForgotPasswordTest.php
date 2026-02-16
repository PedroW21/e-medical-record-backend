<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('sends generic message for existing email', function (): void {
    Notification::fake();

    User::factory()->create(['email' => 'doctor@example.com']);

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'doctor@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Se o e-mail informado estiver cadastrado, você receberá um link para redefinição de senha.');
});

it('sends same generic message for nonexistent email', function (): void {
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Se o e-mail informado estiver cadastrado, você receberá um link para redefinição de senha.');
});

it('fails validation without email', function (): void {
    $response = $this->postJson('/api/forgot-password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});
