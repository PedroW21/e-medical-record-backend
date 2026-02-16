<?php

declare(strict_types=1);

namespace App\Modules\Paciente\DTOs;

use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use App\Modules\Paciente\Http\Requests\UpdatePacienteRequest;
use Illuminate\Support\Carbon;

final readonly class UpdatePacienteDTO
{
    /**
     * @param  string[]  $alergias
     * @param  string[]  $condicoesCronicas
     */
    public function __construct(
        public string $nome,
        public string $cpf,
        public string $telefone,
        public ?string $email,
        public Carbon $dataNascimento,
        public Sexo $sexo,
        public ?TipoSanguineo $tipoSanguineo,
        public ?IntensidadeHabito $historicoTabagismo,
        public ?IntensidadeHabito $historicoAlcool,
        public StatusPaciente $status,
        public array $alergias,
        public array $condicoesCronicas,
        public ?EnderecoDTO $endereco,
    ) {}

    public static function fromRequest(UpdatePacienteRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            cpf: $validated['cpf'],
            telefone: $validated['phone'],
            email: $validated['email'] ?? null,
            dataNascimento: Carbon::parse($validated['birth_date']),
            sexo: Sexo::fromFrontend($validated['gender']),
            tipoSanguineo: isset($validated['blood_type']) ? TipoSanguineo::from($validated['blood_type']) : null,
            historicoTabagismo: isset($validated['medical_history']['smoking']) ? IntensidadeHabito::from($validated['medical_history']['smoking']) : null,
            historicoAlcool: isset($validated['medical_history']['alcohol']) ? IntensidadeHabito::from($validated['medical_history']['alcohol']) : null,
            status: StatusPaciente::from($validated['status'] ?? 'active'),
            alergias: $validated['allergies'] ?? [],
            condicoesCronicas: $validated['chronic_conditions'] ?? [],
            endereco: isset($validated['address']) ? EnderecoDTO::fromArray($validated['address']) : null,
        );
    }
}
