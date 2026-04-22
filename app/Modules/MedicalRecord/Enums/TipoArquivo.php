<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum TipoArquivo: string
{
    case Pdf = 'pdf';
    case Jpg = 'jpg';
    case Jpeg = 'jpeg';
    case Png = 'png';
    case Gif = 'gif';

    public static function fromExtension(string $extension): self
    {
        return self::from(strtolower($extension));
    }

    public function isImage(): bool
    {
        return in_array($this, [self::Jpg, self::Jpeg, self::Png, self::Gif], true);
    }
}
