<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum FileType: string
{
    case Pdf = 'pdf';
    case Jpg = 'jpg';
    case Jpeg = 'jpeg';
    case Png = 'png';
    case Gif = 'gif';

    /**
     * Build the enum from a file extension (case-insensitive).
     *
     * @throws \ValueError When the extension is not an allowed file type.
     */
    public static function fromExtension(string $extension): self
    {
        return self::from(strtolower($extension));
    }

    public function isImage(): bool
    {
        return in_array($this, [self::Jpg, self::Jpeg, self::Png, self::Gif], true);
    }
}
