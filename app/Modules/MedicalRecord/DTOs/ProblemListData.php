<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class ProblemListData
{
    /**
     * @param array<int, array{
     *     problem_id: string,
     *     label: string,
     *     category: string,
     *     is_custom: bool,
     *     selected_variation?: string|null
     * }> $selectedProblems
     * @param  string[]  $customProblems
     */
    public function __construct(
        public array $selectedProblems,
        public array $customProblems,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            selectedProblems: $data['selected_problems'] ?? [],
            customProblems: $data['custom_problems'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'selected_problems' => $this->selectedProblems,
            'custom_problems' => $this->customProblems,
        ];
    }
}
