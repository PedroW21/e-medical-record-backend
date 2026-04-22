<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum AttachmentType: string
{
    case Lab = 'lab';
    case Ecg = 'ecg';
    case Rx = 'rx';
    case Eco = 'eco';
    case Mapa = 'mapa';
    case Mrpa = 'mrpa';
    case Dexa = 'dexa';
    case TesteErgometrico = 'teste_ergometrico';
    case EcodopplerCarotidas = 'ecodoppler_carotidas';
    case ElastografiaHepatica = 'elastografia_hepatica';
    case Cat = 'cat';
    case Cintilografia = 'cintilografia';
    case PeDiabetico = 'pe_diabetico';
    case Holter = 'holter';
    case Polissonografia = 'polissonografia';
    case Documento = 'documento';
    case Outro = 'outro';

    /**
     * Whether this attachment type is eligible for AI parsing.
     */
    public function isParseable(): bool
    {
        return ! in_array($this, [self::Documento, self::Outro], true);
    }

    /**
     * Map this attachment type to the corresponding exam type slug (if any).
     * Returns null for types that do not materialize into a single exam result row:
     * `documento` and `outro` (no AI), and `lab` (goes through the lab analyte path).
     */
    public function toExamType(): ?ExamType
    {
        return match ($this) {
            self::Ecg => ExamType::Ecg,
            self::Rx => ExamType::Xray,
            self::Eco => ExamType::Echo,
            self::Mapa => ExamType::Mapa,
            self::Mrpa => ExamType::Mrpa,
            self::Dexa => ExamType::Dexa,
            self::TesteErgometrico => ExamType::ErgometricTest,
            self::EcodopplerCarotidas => ExamType::CarotidEcodoppler,
            self::ElastografiaHepatica => ExamType::HepaticElastography,
            self::Cat => ExamType::Cat,
            self::Cintilografia => ExamType::Scintigraphy,
            self::PeDiabetico => ExamType::DiabeticFoot,
            self::Holter, self::Polissonografia => ExamType::FreeText,
            self::Lab, self::Documento, self::Outro => null,
        };
    }

    /**
     * Whether this attachment type routes through the lab-analyte path
     * (one PDF → N `valores_laboratoriais` rows) instead of a single exam row.
     */
    public function isLabType(): bool
    {
        return $this === self::Lab;
    }
}
