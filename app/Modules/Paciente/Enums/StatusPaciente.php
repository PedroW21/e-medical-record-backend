<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum StatusPaciente: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
