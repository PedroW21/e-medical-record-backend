<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;

it('lists lab catalog items', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => 'hematologia',
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog');

    $response->assertOk()
        ->assertJsonPath('data.0.id', 'hemo-hemoglobina')
        ->assertJsonPath('data.0.name', 'Hemoglobina')
        ->assertJsonPath('data.0.category', 'hematologia');
});

it('filters lab catalog by category', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => 'hematologia',
        'unidade' => 'g/dL',
        'tipo_resultado' => 'numeric',
    ]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'bio-glicemia',
        'nome' => 'Glicemia de jejum',
        'categoria' => 'bioquimica',
        'unidade' => 'mg/dL',
        'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog?category=hematologia');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'hemo-hemoglobina');
});

it('searches lab catalog by name', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => 'hematologia',
        'unidade' => 'g/dL',
        'tipo_resultado' => 'numeric',
    ]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'bio-glicemia',
        'nome' => 'Glicemia de jejum',
        'categoria' => 'bioquimica',
        'unidade' => 'mg/dL',
        'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog?search=glicemia');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'bio-glicemia');
});

it('paginates the lab catalog', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'tipo_resultado' => 'numeric',
    ]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'bio-glicemia', 'nome' => 'Glicemia de jejum', 'categoria' => 'bioquimica',
        'unidade' => 'mg/dL', 'tipo_resultado' => 'numeric',
    ]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hematocrito', 'nome' => 'Hematócrito', 'categoria' => 'hematologia',
        'unidade' => '%', 'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog?per_page=2');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3);
});

it('rejects unauthenticated access to lab catalog', function (): void {
    $this->getJson('/api/lab-catalog')->assertUnauthorized();
});
