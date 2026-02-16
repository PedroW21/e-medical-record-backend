<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Actions;

use App\Modules\Paciente\DTOs\CreatePacienteDTO;
use App\Modules\Paciente\Models\Alergia;
use App\Modules\Paciente\Models\CondicaoCronica;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Support\Facades\DB;

final class CreatePacienteAction
{
    public function execute(int $userId, CreatePacienteDTO $dto): Paciente
    {
        return DB::transaction(function () use ($userId, $dto): Paciente {
            $paciente = Paciente::query()->create([
                'user_id' => $userId,
                'nome' => $dto->nome,
                'cpf' => $dto->cpf,
                'telefone' => $dto->telefone,
                'email' => $dto->email,
                'data_nascimento' => $dto->dataNascimento,
                'sexo' => $dto->sexo,
                'tipo_sanguineo' => $dto->tipoSanguineo,
                'historico_tabagismo' => $dto->historicoTabagismo,
                'historico_alcool' => $dto->historicoAlcool,
                'status' => $dto->status,
            ]);

            if ($dto->endereco) {
                $paciente->endereco()->create([
                    'cep' => $dto->endereco->cep,
                    'logradouro' => $dto->endereco->logradouro,
                    'numero' => $dto->endereco->numero,
                    'complemento' => $dto->endereco->complemento,
                    'bairro' => $dto->endereco->bairro,
                    'cidade' => $dto->endereco->cidade,
                    'estado' => $dto->endereco->estado,
                ]);
            }

            $this->syncAlergias($paciente, $dto->alergias);
            $this->syncCondicoesCronicas($paciente, $dto->condicoesCronicas);

            return $paciente->load(['endereco', 'alergias', 'condicoesCronicas']);
        });
    }

    /**
     * @param  string[]  $nomes
     */
    private function syncAlergias(Paciente $paciente, array $nomes): void
    {
        if (empty($nomes)) {
            return;
        }

        $ids = collect($nomes)->map(
            fn (string $nome) => Alergia::query()->firstOrCreate(['nome' => $nome])->id
        );

        $paciente->alergias()->sync($ids);
    }

    /**
     * @param  string[]  $nomes
     */
    private function syncCondicoesCronicas(Paciente $paciente, array $nomes): void
    {
        if (empty($nomes)) {
            return;
        }

        $ids = collect($nomes)->map(
            fn (string $nome) => CondicaoCronica::query()->firstOrCreate(['nome' => $nome])->id
        );

        $paciente->condicoesCronicas()->sync($ids);
    }
}
