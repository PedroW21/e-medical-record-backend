<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\StoreLabResultRequest;

final readonly class StoreLabResultDTO
{
    /**
     * @param array<int, LabPanelEntryDTO> $panels
     * @param array<int, LabLooseEntryDTO> $loose
     */
    public function __construct(
        public string $date,
        public array $panels,
        public array $loose,
    ) {}

    public static function fromRequest(StoreLabResultRequest $request): self
    {
        $validated = $request->validated();

        $panels = array_map(
            fn (array $p): LabPanelEntryDTO => LabPanelEntryDTO::fromArray($p),
            $validated['panels'] ?? [],
        );

        $loose = array_map(
            fn (array $l): LabLooseEntryDTO => LabLooseEntryDTO::fromArray($l),
            $validated['loose'] ?? [],
        );

        return new self(
            date: $validated['date'],
            panels: $panels,
            loose: $loose,
        );
    }
}
