<?php

declare(strict_types=1);

namespace App\Modules\Patient\DTOs;

/**
 * Data Transfer Object for patient address data.
 */
final readonly class AddressDTO
{
    public function __construct(
        public string $zipCode,
        public string $street,
        public string $number,
        public ?string $complement,
        public string $neighborhood,
        public string $city,
        public string $state,
    ) {}

    /**
     * @param  array{cep: string, street: string, number: string, complement?: string|null, neighborhood: string, city: string, state: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            zipCode: $data['cep'],
            street: $data['street'],
            number: $data['number'],
            complement: $data['complement'] ?? null,
            neighborhood: $data['neighborhood'],
            city: $data['city'],
            state: $data['state'],
        );
    }
}
