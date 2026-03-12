<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class ConductData
{
    /**
     * @param array<int, array{
     *     type: string,
     *     label: string,
     *     default_text: string,
     *     custom_text?: string|null
     * }> $diets
     * @param array{
     *     default_text: string,
     *     custom_text?: string|null
     * } $physicalActivity
     */
    public function __construct(
        public bool $sleepHygiene,
        public string $sleepDefaultText,
        public ?string $sleepObservations,
        public array $diets,
        public array $physicalActivity,
        public bool $xenobioticsRestriction,
        public string $xenobioticsDefaultText,
        public ?string $xenobioticsObservations,
        public bool $medicationCompliance,
        public string $medicationDefaultText,
        public ?string $medicationObservations,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sleepHygiene: (bool) $data['sleep_hygiene'],
            sleepDefaultText: $data['sleep_default_text'],
            sleepObservations: $data['sleep_observations'] ?? null,
            diets: $data['diets'] ?? [],
            physicalActivity: $data['physical_activity'],
            xenobioticsRestriction: (bool) $data['xenobiotics_restriction'],
            xenobioticsDefaultText: $data['xenobiotics_default_text'],
            xenobioticsObservations: $data['xenobiotics_observations'] ?? null,
            medicationCompliance: (bool) $data['medication_compliance'],
            medicationDefaultText: $data['medication_default_text'],
            medicationObservations: $data['medication_observations'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sleep_hygiene' => $this->sleepHygiene,
            'sleep_default_text' => $this->sleepDefaultText,
            'sleep_observations' => $this->sleepObservations,
            'diets' => $this->diets,
            'physical_activity' => $this->physicalActivity,
            'xenobiotics_restriction' => $this->xenobioticsRestriction,
            'xenobiotics_default_text' => $this->xenobioticsDefaultText,
            'xenobiotics_observations' => $this->xenobioticsObservations,
            'medication_compliance' => $this->medicationCompliance,
            'medication_default_text' => $this->medicationDefaultText,
            'medication_observations' => $this->medicationObservations,
        ];
    }
}
