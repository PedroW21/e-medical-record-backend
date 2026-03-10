<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum MedicalRecordType: string
{
    case FirstVisit = 'first_visit';
    case FollowUp = 'follow_up';
    case PreAnesthetic = 'pre_anesthetic';
}
