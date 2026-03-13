<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class LabPanelValueDTO
{
    public function __construct(
        public string $analyteId,
        public string $value,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            analyteId: $item['analyte_id'],
            value: (string) $item['value'],
        );
    }
}
