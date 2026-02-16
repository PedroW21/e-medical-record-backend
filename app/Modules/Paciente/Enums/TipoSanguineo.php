<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum TipoSanguineo: string
{
    case APositivo = 'A+';
    case ANegativo = 'A-';
    case BPositivo = 'B+';
    case BNegativo = 'B-';
    case ABPositivo = 'AB+';
    case ABNegativo = 'AB-';
    case OPositivo = 'O+';
    case ONegativo = 'O-';
}
