<?php

declare(strict_types=1);

namespace App\Modules\Paciente\DTOs;

final readonly class EnderecoDTO
{
    public function __construct(
        public string $cep,
        public string $logradouro,
        public string $numero,
        public ?string $complemento,
        public string $bairro,
        public string $cidade,
        public string $estado,
    ) {}

    /**
     * @param  array{cep: string, street: string, number: string, complement?: string|null, neighborhood: string, city: string, state: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cep: $data['cep'],
            logradouro: $data['street'],
            numero: $data['number'],
            complemento: $data['complement'] ?? null,
            bairro: $data['neighborhood'],
            cidade: $data['city'],
            estado: $data['state'],
        );
    }
}
