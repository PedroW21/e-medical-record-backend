<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\AnvisaList;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Medicamento;
use App\Modules\MedicalRecord\Services\RecipeTypeGuesser;

it('returns normal for items without medication_id', function (): void {
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_name' => 'Paracetamol', 'dosage' => '500mg']],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::Normal);
});

it('returns yellow_a for A1 medication', function (): void {
    $med = Medicamento::factory()->controlled(AnvisaList::A1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_id' => $med->id, 'medication_name' => $med->nome]],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::YellowA);
});

it('returns blue_b for B1 medication', function (): void {
    $med = Medicamento::factory()->controlled(AnvisaList::B1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_id' => $med->id, 'medication_name' => $med->nome]],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::BlueB);
});

it('returns white_c1 for C1 medication', function (): void {
    $med = Medicamento::factory()->controlled(AnvisaList::C1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_id' => $med->id, 'medication_name' => $med->nome]],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::WhiteC1);
});

it('picks most restrictive when mixing controlled levels', function (): void {
    $medC1 = Medicamento::factory()->controlled(AnvisaList::C1)->create();
    $medB1 = Medicamento::factory()->controlled(AnvisaList::B1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [
            ['medication_id' => $medC1->id, 'medication_name' => $medC1->nome],
            ['medication_id' => $medB1->id, 'medication_name' => $medB1->nome],
        ],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::BlueB);
});

it('returns normal for procedure subtypes regardless of items', function (): void {
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['type' => 'neural_therapy', 'description' => 'Test']],
        subtipo: PrescriptionSubType::Procedure,
    );

    expect($result)->toBe(RecipeType::Normal);
});

it('returns normal for ozonotherapy subtypes', function (): void {
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['description' => 'Protocolo ozônio']],
        subtipo: PrescriptionSubType::Ozonotherapy,
    );

    expect($result)->toBe(RecipeType::Normal);
});
