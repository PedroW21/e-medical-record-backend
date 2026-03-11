<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum MedicalRecordStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
}
