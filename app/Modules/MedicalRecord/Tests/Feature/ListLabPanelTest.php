<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;

it('lists lab panels', function (): void {
    $doctor = User::factory()->doctor()->create();
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => 'hematologia',
        'subsecoes' => [['label' => 'Série Vermelha', 'analytes' => [['id' => 'hemo-hemacias', 'name' => 'Hemácias']]]],
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-panels');

    $response->assertOk()
        ->assertJsonPath('data.0.id', 'hemograma-completo')
        ->assertJsonPath('data.0.name', 'Hemograma Completo')
        ->assertJsonPath('data.0.category', 'hematologia')
        ->assertJsonPath('data.0.subsections.0.label', 'Série Vermelha');
});

it('filters lab panels by category', function (): void {
    $doctor = User::factory()->doctor()->create();
    PainelLaboratorial::query()->create([
        'id' => 'hemograma',
        'nome' => 'Hemograma',
        'categoria' => 'hematologia',
        'subsecoes' => [],
    ]);
    PainelLaboratorial::query()->create([
        'id' => 'lipidios',
        'nome' => 'Lipídios',
        'categoria' => 'bioquimica',
        'subsecoes' => [],
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-panels?category=hematologia');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'hemograma');
});

it('shows a single lab panel', function (): void {
    $doctor = User::factory()->doctor()->create();
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo',
        'nome' => 'Hemograma Completo',
        'categoria' => 'hematologia',
        'subsecoes' => [['label' => 'Série Vermelha', 'analytes' => []]],
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-panels/hemograma-completo');

    $response->assertOk()
        ->assertJsonPath('data.id', 'hemograma-completo')
        ->assertJsonPath('data.name', 'Hemograma Completo')
        ->assertJsonPath('data.category', 'hematologia');
});

it('returns 404 for a non-existent lab panel', function (): void {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)->getJson('/api/lab-panels/non-existent')->assertNotFound();
});

it('rejects unauthenticated access to lab panels', function (): void {
    $this->getJson('/api/lab-panels')->assertUnauthorized();
});
