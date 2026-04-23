<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum InjectableProtocolRoute: string
{
    case Im = 'im';
    case Ev = 'ev';
    case Combined = 'combined';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
