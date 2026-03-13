<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class LabLooseEntryDTO
{
    public function __construct(
        public string $name,
        public string $value,
        public string $unit,
        public ?string $referenceRange = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            value: (string) $data['value'],
            unit: $data['unit'],
            referenceRange: $data['reference_range'] ?? null,
        );
    }
}
