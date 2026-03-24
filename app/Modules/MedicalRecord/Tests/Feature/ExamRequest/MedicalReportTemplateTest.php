<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloRelatorioMedico;

it('lists medical report templates for the authenticated user', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();

    ModeloRelatorioMedico::factory()->count(2)->create(['user_id' => $doctor->id]);
    ModeloRelatorioMedico::factory()->count(3)->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->getJson('/api/medical-report-templates');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('includes default templates in listing', function (): void {
    $doctor = User::factory()->doctor()->create();

    ModeloRelatorioMedico::factory()->count(2)->create(['user_id' => $doctor->id]);
    ModeloRelatorioMedico::factory()->create(['user_id' => null]);

    $response = $this->actingAs($doctor)->getJson('/api/medical-report-templates');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a medical report template', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/medical-report-templates', [
        'name' => 'Atestado padrão',
        'body_template' => 'Atesto para os devidos fins que o(a) paciente {{NOME_PACIENTE}}, portador(a) do diagnóstico {{CID_10}}, encontra-se sob meus cuidados médicos.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Atestado padrão')
        ->assertJsonPath('data.body_template', 'Atesto para os devidos fins que o(a) paciente {{NOME_PACIENTE}}, portador(a) do diagnóstico {{CID_10}}, encontra-se sob meus cuidados médicos.');

    $this->assertDatabaseHas('modelos_relatorio_medico', [
        'user_id' => $doctor->id,
        'nome' => 'Atestado padrão',
    ]);
});

it('updates a medical report template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $template = ModeloRelatorioMedico::factory()->create(['user_id' => $doctor->id, 'nome' => 'Atestado antigo']);

    $response = $this->actingAs($doctor)->putJson("/api/medical-report-templates/{$template->id}", [
        'name' => 'Atestado atualizado',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Atestado atualizado');

    $this->assertDatabaseHas('modelos_relatorio_medico', [
        'id' => $template->id,
        'nome' => 'Atestado atualizado',
    ]);
});

it('deletes a medical report template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $template = ModeloRelatorioMedico::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/medical-report-templates/{$template->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('modelos_relatorio_medico', ['id' => $template->id]);
});

it('rejects update on another user template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $template = ModeloRelatorioMedico::factory()->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->putJson("/api/medical-report-templates/{$template->id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertForbidden();
});

it('validates required fields on store', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/medical-report-templates', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'body_template']);
});
