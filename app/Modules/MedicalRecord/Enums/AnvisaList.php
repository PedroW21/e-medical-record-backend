<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum AnvisaList: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case A3 = 'A3';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';
    case C3 = 'C3';
    case C4 = 'C4';
    case C5 = 'C5';

    /**
     * Map ANVISA list to the required recipe type.
     */
    public function requiredRecipeType(): RecipeType
    {
        return match ($this) {
            self::A1, self::A2, self::A3 => RecipeType::YellowA,
            self::B1, self::B2 => RecipeType::BlueB,
            self::C1, self::C2, self::C3, self::C4, self::C5 => RecipeType::WhiteC1,
        };
    }
}
