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
}
