<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum LabResultType: string
{
    case Numeric = 'numeric';
    case Qualitative = 'qualitative';
    case Titer = 'titer';
    case Descriptive = 'descriptive';
}
