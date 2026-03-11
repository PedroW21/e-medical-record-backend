<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum RecipeType: string
{
    case Normal = 'normal';
    case WhiteC1 = 'white_c1';
    case BlueB = 'blue_b';
    case YellowA = 'yellow_a';

    /**
     * Priority for auto-guess (higher = more restrictive).
     */
    public function priority(): int
    {
        return match ($this) {
            self::Normal => 0,
            self::WhiteC1 => 1,
            self::BlueB => 2,
            self::YellowA => 3,
        };
    }
}
