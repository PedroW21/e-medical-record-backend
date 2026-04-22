<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class ConfirmAttachmentDTO
{
    /**
     * @param  array<string, mixed>  $examData  The edited/confirmed payload the doctor reviewed.
     */
    public function __construct(
        public int $attachmentId,
        public array $examData,
    ) {}
}
