<?php

declare(strict_types=1);

namespace App\Modules\Patient\Actions;

use App\Modules\Patient\DTOs\CreatePatientDTO;
use App\Modules\Patient\Models\Alergia;
use App\Modules\Patient\Models\CondicaoCronica;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Support\Facades\DB;

final class CreatePatientAction
{
    public function execute(int $userId, CreatePatientDTO $dto): Paciente
    {
        return DB::transaction(function () use ($userId, $dto): Paciente {
            $patient = Paciente::query()->create([
                'user_id' => $userId,
                'nome' => $dto->name,
                'cpf' => $dto->cpf,
                'telefone' => $dto->phone,
                'email' => $dto->email,
                'data_nascimento' => $dto->birthDate,
                'sexo' => $dto->gender,
                'tipo_sanguineo' => $dto->bloodType,
                'historico_tabagismo' => $dto->smokingHistory,
                'historico_alcool' => $dto->alcoholHistory,
                'status' => $dto->status,
            ]);

            if ($dto->address) {
                $patient->endereco()->create([
                    'cep' => $dto->address->zipCode,
                    'logradouro' => $dto->address->street,
                    'numero' => $dto->address->number,
                    'complemento' => $dto->address->complement,
                    'bairro' => $dto->address->neighborhood,
                    'cidade' => $dto->address->city,
                    'estado' => $dto->address->state,
                ]);
            }

            $this->syncAllergies($patient, $dto->allergies);
            $this->syncChronicConditions($patient, $dto->chronicConditions);

            return $patient->load(['endereco', 'alergias', 'condicoesCronicas']);
        });
    }

    /**
     * @param  string[]  $names
     */
    private function syncAllergies(Paciente $patient, array $names): void
    {
        if (empty($names)) {
            return;
        }

        $ids = collect($names)->map(
            fn (string $name) => Alergia::query()->firstOrCreate(['nome' => $name])->id
        );

        $patient->alergias()->sync($ids);
    }

    /**
     * @param  string[]  $names
     */
    private function syncChronicConditions(Paciente $patient, array $names): void
    {
        if (empty($names)) {
            return;
        }

        $ids = collect($names)->map(
            fn (string $name) => CondicaoCronica::query()->firstOrCreate(['nome' => $name])->id
        );

        $patient->condicoesCronicas()->sync($ids);
    }
}
