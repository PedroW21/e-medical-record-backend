<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloSolicitacaoExame;

it('lists exam request models for the authenticated user', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();

    ModeloSolicitacaoExame::factory()->count(3)->create(['user_id' => $doctor->id]);
    ModeloSolicitacaoExame::factory()->count(2)->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->getJson('/api/exam-request-models');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('includes default models in listing', function (): void {
    $doctor = User::factory()->doctor()->create();

    ModeloSolicitacaoExame::factory()->count(2)->create(['user_id' => $doctor->id]);
    ModeloSolicitacaoExame::factory()->create(['user_id' => null]);

    $response = $this->actingAs($doctor)->getJson('/api/exam-request-models');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters models by category', function (): void {
    $doctor = User::factory()->doctor()->create();

    ModeloSolicitacaoExame::factory()->create(['user_id' => $doctor->id, 'categoria' => 'Rotina']);
    ModeloSolicitacaoExame::factory()->create(['user_id' => $doctor->id, 'categoria' => 'Cardiologia']);

    $response = $this->actingAs($doctor)->getJson('/api/exam-request-models?category=Rotina');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('creates a custom exam request model', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/exam-request-models', [
        'name' => 'Rotina anual completa',
        'category' => 'Rotina',
        'items' => [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566'],
            ['id' => 'glicemia', 'name' => 'Glicemia em jejum', 'tuss_code' => '40302213'],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Rotina anual completa')
        ->assertJsonPath('data.category', 'Rotina');

    $this->assertDatabaseHas('modelos_solicitacao_exames', [
        'user_id' => $doctor->id,
        'nome' => 'Rotina anual completa',
        'categoria' => 'Rotina',
    ]);
});

it('updates a custom model', function (): void {
    $doctor = User::factory()->doctor()->create();
    $model = ModeloSolicitacaoExame::factory()->create(['user_id' => $doctor->id, 'nome' => 'Rotina antiga']);

    $response = $this->actingAs($doctor)->putJson("/api/exam-request-models/{$model->id}", [
        'name' => 'Rotina atualizada',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Rotina atualizada');

    $this->assertDatabaseHas('modelos_solicitacao_exames', [
        'id' => $model->id,
        'nome' => 'Rotina atualizada',
    ]);
});

it('deletes a custom model', function (): void {
    $doctor = User::factory()->doctor()->create();
    $model = ModeloSolicitacaoExame::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/exam-request-models/{$model->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('modelos_solicitacao_exames', ['id' => $model->id]);
});

it('rejects update on another user model', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $model = ModeloSolicitacaoExame::factory()->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->putJson("/api/exam-request-models/{$model->id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertForbidden();
});

it('rejects delete on another user model', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $model = ModeloSolicitacaoExame::factory()->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/exam-request-models/{$model->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('modelos_solicitacao_exames', ['id' => $model->id]);
});

it('validates required fields on store', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/exam-request-models', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'items']);
});
