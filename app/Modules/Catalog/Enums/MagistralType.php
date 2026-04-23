<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum MagistralType: string
{
    case Farmaco = 'farmaco';
    case Alvo = 'alvo';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
