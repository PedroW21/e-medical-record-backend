<?php

declare(strict_types=1);

namespace App\Modules\Patient\Enums;

enum PatientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
