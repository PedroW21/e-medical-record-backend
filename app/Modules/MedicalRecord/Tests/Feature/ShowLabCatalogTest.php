<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;

it('shows a single lab catalog item', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => 'hematologia',
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog/hemo-hemoglobina');

    $response->assertOk()
        ->assertJsonPath('data.id', 'hemo-hemoglobina')
        ->assertJsonPath('data.name', 'Hemoglobina')
        ->assertJsonPath('data.category', 'hematologia')
        ->assertJsonPath('data.unit', 'g/dL')
        ->assertJsonPath('data.reference_range', '13,5-17,5');
});

it('returns 404 for a non-existent catalog item', function (): void {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)->getJson('/api/lab-catalog/non-existent')->assertNotFound();
});

it('rejects unauthenticated access to a catalog item', function (): void {
    $this->getJson('/api/lab-catalog/hemo-hemoglobina')->assertUnauthorized();
});
