<?php

declare(strict_types=1);

namespace App\Modules\Patient\Enums;

enum Gender: string
{
    case Male = 'masculino';
    case Female = 'feminino';

    public function toFrontend(): string
    {
        return match ($this) {
            self::Male => 'male',
            self::Female => 'female',
        };
    }

    public static function fromFrontend(string $value): self
    {
        return match ($value) {
            'male' => self::Male,
            'female' => self::Female,
            default => throw new \ValueError("Invalid gender value: {$value}"),
        };
    }
}
