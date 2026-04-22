<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\AttachmentType;
use Illuminate\Http\UploadedFile;

final readonly class UploadAttachmentDTO
{
    public function __construct(
        public int $prontuarioId,
        public AttachmentType $tipoAnexo,
        public UploadedFile $file,
        public ?string $nome = null,
    ) {}
}
