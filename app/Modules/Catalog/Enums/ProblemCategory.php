<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum ProblemCategory: string
{
    case Inflammatory = 'inflammatory';
    case Hematologic = 'hematologic';
    case Metabolic = 'metabolic';
    case Gastrointestinal = 'gastrointestinal';
    case Endocrine = 'endocrine';
    case Renal = 'renal';
    case Musculoskeletal = 'musculoskeletal';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
