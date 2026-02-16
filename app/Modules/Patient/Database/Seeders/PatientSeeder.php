<?php

declare(strict_types=1);

namespace App\Modules\Patient\Database\Seeders;

use App\Models\User;
use App\Modules\Patient\Enums\BloodType;
use App\Modules\Patient\Enums\Gender;
use App\Modules\Patient\Enums\HabitIntensity;
use App\Modules\Patient\Enums\PatientStatus;
use App\Modules\Patient\Models\Alergia;
use App\Modules\Patient\Models\CondicaoCronica;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Seeder;

final class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $glayson = User::query()->where('email', 'glayson.verner@verner.goulart')->first();
        $pedro = User::query()->where('email', 'pedro.verner@verner.goulart')->first();

        if (! $glayson || ! $pedro) {
            return;
        }

        $this->createPatientsForDoctor($glayson);
        $this->createPatientsForDoctor($pedro);
    }

    private function createPatientsForDoctor(User $doctor): void
    {
        $patients = [
            [
                'nome' => 'Maria da Silva Santos',
                'cpf' => '123.456.789-00',
                'telefone' => '(11) 99876-5432',
                'email' => 'maria.silva@email.com',
                'data_nascimento' => '1985-03-15',
                'sexo' => Gender::Female,
                'tipo_sanguineo' => BloodType::APositivo,
                'historico_tabagismo' => HabitIntensity::None,
                'historico_alcool' => HabitIntensity::Light,
                'status' => PatientStatus::Active,
                'allergies' => ['Penicilina', 'Dipirona'],
                'conditions' => ['Hipertensão Arterial'],
                'address' => [
                    'cep' => '04101-000',
                    'logradouro' => 'Rua Vergueiro',
                    'numero' => '1000',
                    'complemento' => 'Apto 42',
                    'bairro' => 'Vila Mariana',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                ],
            ],
            [
                'nome' => 'João Carlos Ferreira',
                'cpf' => '987.654.321-00',
                'telefone' => '(11) 98765-4321',
                'email' => 'joao.ferreira@email.com',
                'data_nascimento' => '1978-07-22',
                'sexo' => Gender::Male,
                'tipo_sanguineo' => BloodType::OPositivo,
                'historico_tabagismo' => HabitIntensity::Moderate,
                'historico_alcool' => HabitIntensity::Moderate,
                'status' => PatientStatus::Active,
                'allergies' => ['Sulfa'],
                'conditions' => ['Diabetes Tipo 2', 'Hipertensão Arterial'],
                'address' => [
                    'cep' => '01310-100',
                    'logradouro' => 'Avenida Paulista',
                    'numero' => '1578',
                    'complemento' => null,
                    'bairro' => 'Bela Vista',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                ],
            ],
            [
                'nome' => 'Ana Paula Oliveira',
                'cpf' => '456.789.123-00',
                'telefone' => '(21) 99123-4567',
                'email' => null,
                'data_nascimento' => '1990-11-08',
                'sexo' => Gender::Female,
                'tipo_sanguineo' => BloodType::BNegativo,
                'historico_tabagismo' => HabitIntensity::None,
                'historico_alcool' => HabitIntensity::None,
                'status' => PatientStatus::Active,
                'allergies' => [],
                'conditions' => ['Asma'],
                'address' => null,
            ],
            [
                'nome' => 'Roberto Almeida Costa',
                'cpf' => '321.654.987-00',
                'telefone' => '(11) 97654-3210',
                'email' => 'roberto.costa@email.com',
                'data_nascimento' => '1965-01-30',
                'sexo' => Gender::Male,
                'tipo_sanguineo' => BloodType::ABPositivo,
                'historico_tabagismo' => HabitIntensity::Intense,
                'historico_alcool' => HabitIntensity::Light,
                'status' => PatientStatus::Inactive,
                'allergies' => ['AAS', 'Ibuprofeno'],
                'conditions' => ['DPOC', 'Insuficiência Cardíaca'],
                'address' => [
                    'cep' => '04538-132',
                    'logradouro' => 'Rua Funchal',
                    'numero' => '418',
                    'complemento' => 'Sala 35',
                    'bairro' => 'Vila Olímpia',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                ],
            ],
        ];

        foreach ($patients as $data) {
            $patient = Paciente::query()->updateOrCreate(
                ['user_id' => $doctor->id, 'cpf' => $data['cpf']],
                [
                    'nome' => $data['nome'],
                    'telefone' => $data['telefone'],
                    'email' => $data['email'],
                    'data_nascimento' => $data['data_nascimento'],
                    'sexo' => $data['sexo'],
                    'tipo_sanguineo' => $data['tipo_sanguineo'],
                    'historico_tabagismo' => $data['historico_tabagismo'],
                    'historico_alcool' => $data['historico_alcool'],
                    'status' => $data['status'],
                ],
            );

            if (! empty($data['allergies'])) {
                $allergyIds = collect($data['allergies'])->map(
                    fn (string $nome) => Alergia::query()->firstOrCreate(['nome' => $nome])->id
                );
                $patient->alergias()->sync($allergyIds);
            }

            if (! empty($data['conditions'])) {
                $conditionIds = collect($data['conditions'])->map(
                    fn (string $nome) => CondicaoCronica::query()->firstOrCreate(['nome' => $nome])->id
                );
                $patient->condicoesCronicas()->sync($conditionIds);
            }

            if ($data['address']) {
                $patient->endereco()->updateOrCreate(
                    ['paciente_id' => $patient->id],
                    $data['address'],
                );
            }
        }
    }
}
