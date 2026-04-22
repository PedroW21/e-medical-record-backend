<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum StatusProcessamento: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Confirmed = 'confirmed';
}
