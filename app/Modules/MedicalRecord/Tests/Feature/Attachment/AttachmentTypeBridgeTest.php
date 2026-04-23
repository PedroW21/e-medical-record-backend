<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ExamType;

it('maps parseable non-lab types to a corresponding ExamType', function (): void {
    $pairs = [
        [AttachmentType::Ecg, ExamType::Ecg],
        [AttachmentType::Rx, ExamType::Xray],
        [AttachmentType::Eco, ExamType::Echo],
        [AttachmentType::Mapa, ExamType::Mapa],
        [AttachmentType::Mrpa, ExamType::Mrpa],
        [AttachmentType::Dexa, ExamType::Dexa],
        [AttachmentType::TesteErgometrico, ExamType::ErgometricTest],
        [AttachmentType::EcodopplerCarotidas, ExamType::CarotidEcodoppler],
        [AttachmentType::ElastografiaHepatica, ExamType::HepaticElastography],
        [AttachmentType::Cat, ExamType::Cat],
        [AttachmentType::Cintilografia, ExamType::Scintigraphy],
        [AttachmentType::PeDiabetico, ExamType::DiabeticFoot],
    ];

    foreach ($pairs as [$attachment, $exam]) {
        expect($attachment->toExamType())->toBe($exam);
    }
});

it('maps holter and polissonografia to free text exam type', function (): void {
    expect(AttachmentType::Holter->toExamType())->toBe(ExamType::FreeText)
        ->and(AttachmentType::Polissonografia->toExamType())->toBe(ExamType::FreeText);
});

it('returns null for documento, outro and lab', function (): void {
    expect(AttachmentType::Documento->toExamType())->toBeNull()
        ->and(AttachmentType::Outro->toExamType())->toBeNull()
        ->and(AttachmentType::Lab->toExamType())->toBeNull();
});

it('recognises only Lab as a lab type', function (): void {
    expect(AttachmentType::Lab->isLabType())->toBeTrue();

    foreach (AttachmentType::cases() as $case) {
        if ($case === AttachmentType::Lab) {
            continue;
        }

        expect($case->isLabType())->toBeFalse();
    }
});
