<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum IntensidadeHabito: string
{
    case None = 'none';
    case Light = 'light';
    case Moderate = 'moderate';
    case Intense = 'intense';
}
