<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class LabPanelEntryDTO
{
    /**
     * @param array<int, LabPanelValueDTO> $values
     */
    public function __construct(
        public string $panelId,
        public string $panelName,
        public bool $isCustom,
        public array $values,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            panelId: $data['panel_id'],
            panelName: $data['panel_name'],
            isCustom: (bool) ($data['is_custom'] ?? false),
            values: array_map(
                fn (array $v): LabPanelValueDTO => LabPanelValueDTO::fromArray($v),
                $data['values'],
            ),
        );
    }
}
