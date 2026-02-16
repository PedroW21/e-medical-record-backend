<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum Sexo: string
{
    case Masculino = 'masculino';
    case Feminino = 'feminino';

    public function toFrontend(): string
    {
        return match ($this) {
            self::Masculino => 'male',
            self::Feminino => 'female',
        };
    }

    public static function fromFrontend(string $value): self
    {
        return match ($value) {
            'male' => self::Masculino,
            'female' => self::Feminino,
            default => throw new \ValueError("Valor inválido para sexo: {$value}"),
        };
    }
}
