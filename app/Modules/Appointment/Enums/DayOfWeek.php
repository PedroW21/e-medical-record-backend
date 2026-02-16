<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum DayOfWeek: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    /**
     * Return the Portuguese label for the day.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sunday => 'Domingo',
            self::Monday => 'Segunda-feira',
            self::Tuesday => 'Terça-feira',
            self::Wednesday => 'Quarta-feira',
            self::Thursday => 'Quinta-feira',
            self::Friday => 'Sexta-feira',
            self::Saturday => 'Sábado',
        };
    }
}
