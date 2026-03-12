<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class PhysicalExamData
{
    /**
     * @param array{
     *     is_normal: bool,
     *     rhythm?: string|null,
     *     heart_sounds?: string|null,
     *     murmur?: string|null,
     *     observations?: string|null
     * } $cardiac
     * @param array{
     *     is_normal: bool,
     *     vesicular_murmur?: string|null,
     *     adventitious_sounds?: string|null,
     *     observations?: string|null
     * } $respiratory
     * @param array{
     *     varicose_veins: bool,
     *     edema: bool,
     *     lymphedema: bool,
     *     ulcer: bool,
     *     asymmetry: bool,
     *     sensitivity_alteration: bool,
     *     motricity_alteration: bool,
     *     observations?: string|null
     * } $lowerLimbs
     * @param array{
     *     status: string,
     *     prosthesis_location?: string[]|null,
     *     diseases?: string[]|null,
     *     observations?: string|null
     * } $dentition
     * @param array{
     *     status: string,
     *     observations?: string|null
     * } $gums
     */
    public function __construct(
        public array $cardiac,
        public array $respiratory,
        public array $lowerLimbs,
        public array $dentition,
        public array $gums,
        public ?int $ceap = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cardiac: $data['cardiac'],
            respiratory: $data['respiratory'],
            lowerLimbs: $data['lower_limbs'],
            dentition: $data['dentition'],
            gums: $data['gums'],
            ceap: isset($data['ceap']) ? (int) $data['ceap'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cardiac' => $this->cardiac,
            'respiratory' => $this->respiratory,
            'lower_limbs' => $this->lowerLimbs,
            'dentition' => $this->dentition,
            'gums' => $this->gums,
            'ceap' => $this->ceap,
        ];
    }
}
