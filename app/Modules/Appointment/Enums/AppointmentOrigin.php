<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum AppointmentOrigin: string
{
    case Internal = 'internal';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Interno',
            self::Public => 'Público',
        };
    }
}
