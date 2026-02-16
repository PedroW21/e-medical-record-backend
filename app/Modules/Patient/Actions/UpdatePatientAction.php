<?php

declare(strict_types=1);

namespace App\Modules\Patient\Actions;

use App\Modules\Patient\DTOs\UpdatePatientDTO;
use App\Modules\Patient\Models\Alergia;
use App\Modules\Patient\Models\CondicaoCronica;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Support\Facades\DB;

final class UpdatePatientAction
{
    public function execute(Paciente $patient, UpdatePatientDTO $dto): Paciente
    {
        return DB::transaction(function () use ($patient, $dto): Paciente {
            $patient->update([
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
                $patient->endereco()->updateOrCreate(
                    ['paciente_id' => $patient->id],
                    [
                        'cep' => $dto->address->zipCode,
                        'logradouro' => $dto->address->street,
                        'numero' => $dto->address->number,
                        'complemento' => $dto->address->complement,
                        'bairro' => $dto->address->neighborhood,
                        'cidade' => $dto->address->city,
                        'estado' => $dto->address->state,
                    ]
                );
            } else {
                $patient->endereco?->delete();
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
        $ids = collect($names)->map(
            fn (string $name) => CondicaoCronica::query()->firstOrCreate(['nome' => $name])->id
        );

        $patient->condicoesCronicas()->sync($ids);
    }
}
