<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;

it('lists templates for authenticated doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    ModeloPrescricao::factory()->count(3)->create(['user_id' => $doctor->id]);
    ModeloPrescricao::factory()->count(2)->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->getJson('/api/prescription-templates');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters templates by subtype', function (): void {
    $doctor = User::factory()->doctor()->create();
    ModeloPrescricao::factory()->create(['user_id' => $doctor->id, 'subtipo' => 'allopathic']);
    ModeloPrescricao::factory()->create(['user_id' => $doctor->id, 'subtipo' => 'magistral']);

    $response = $this->actingAs($doctor)->getJson('/api/prescription-templates?subtype=magistral');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('creates a prescription template', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/prescription-templates', [
        'name' => 'Protocolo Dor Crônica',
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_name' => 'Paracetamol 500mg',
                'dosage' => '1 comprimido',
                'frequency' => '8/8h',
                'duration' => '7 dias',
            ],
        ],
        'tags' => ['dor', 'crônica'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Protocolo Dor Crônica')
        ->assertJsonPath('data.subtype', 'allopathic');
});

it('rejects creation without required fields', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/prescription-templates', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'subtype', 'items']);
});

it('updates own template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $template = ModeloPrescricao::factory()->create(['user_id' => $doctor->id, 'nome' => 'Old Name']);

    $response = $this->actingAs($doctor)->putJson("/api/prescription-templates/{$template->id}", [
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('deletes own template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $template = ModeloPrescricao::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/prescription-templates/{$template->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('modelos_prescricao', ['id' => $template->id]);
});

it('rejects update of another doctor template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $template = ModeloPrescricao::factory()->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->putJson("/api/prescription-templates/{$template->id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertForbidden();
});

it('rejects deletion of another doctor template', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $template = ModeloPrescricao::factory()->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/prescription-templates/{$template->id}");

    $response->assertForbidden();
});

it('rejects unauthenticated access', function (): void {
    $response = $this->getJson('/api/prescription-templates');

    $response->assertUnauthorized();
});

it('returns 404 when updating nonexistent template', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/prescription-templates/99999', [
        'name' => 'New Name',
    ]);

    $response->assertNotFound();
});

it('returns 404 when deleting nonexistent template', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->deleteJson('/api/prescription-templates/99999');

    $response->assertNotFound();
});

it('clears tags when null is explicitly sent', function (): void {
    $doctor = User::factory()->doctor()->create();
    $template = ModeloPrescricao::factory()->create([
        'user_id' => $doctor->id,
        'tags' => ['antibiótico', 'infecção'],
    ]);

    $response = $this->actingAs($doctor)->putJson("/api/prescription-templates/{$template->id}", [
        'tags' => null,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.tags', null);

    $this->assertDatabaseHas('modelos_prescricao', [
        'id' => $template->id,
        'tags' => null,
    ]);
});
