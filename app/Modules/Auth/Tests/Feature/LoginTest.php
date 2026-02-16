<?php

declare(strict_types=1);

use App\Models\User;

it('logs in with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'doctor@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'doctor@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'role', 'crm', 'specialty', 'avatar_url', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.email', 'doctor@example.com');
});

it('fails login with wrong password', function (): void {
    User::factory()->create([
        'email' => 'doctor@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'doctor@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('fails login with nonexistent email', function (): void {
    $response = $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('fails login without required fields', function (): void {
    $response = $this->postJson('/api/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('returns role and profile fields on login', function (): void {
    $user = User::factory()->doctor()->create([
        'email' => 'doctor@example.com',
        'password' => bcrypt('password'),
        'crm' => 'CRM/SP 123456',
        'specialty' => 'Cardiologia',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'doctor@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.role', 'doctor')
        ->assertJsonPath('data.crm', 'CRM/SP 123456')
        ->assertJsonPath('data.specialty', 'Cardiologia');
});

it('fails login with invalid email format', function (): void {
    $response = $this->postJson('/api/login', [
        'email' => 'not-an-email',
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});
