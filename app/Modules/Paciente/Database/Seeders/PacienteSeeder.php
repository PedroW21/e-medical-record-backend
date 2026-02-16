<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Seeders;

use App\Models\User;
use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use App\Modules\Paciente\Models\Alergia;
use App\Modules\Paciente\Models\CondicaoCronica;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Database\Seeder;

final class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $glayson = User::query()->where('email', 'glayson.verner@verner.goulart')->first();
        $pedro = User::query()->where('email', 'pedro.verner@verner.goulart')->first();

        if (! $glayson || ! $pedro) {
            return;
        }

        $this->criarPacientesParaMedico($glayson);
        $this->criarPacientesParaMedico($pedro);
    }

    private function criarPacientesParaMedico(User $medico): void
    {
        $pacientes = [
            [
                'nome' => 'Maria da Silva Santos',
                'cpf' => '123.456.789-00',
                'telefone' => '(11) 99876-5432',
                'email' => 'maria.silva@email.com',
                'data_nascimento' => '1985-03-15',
                'sexo' => Sexo::Feminino,
                'tipo_sanguineo' => TipoSanguineo::APositivo,
                'historico_tabagismo' => IntensidadeHabito::None,
                'historico_alcool' => IntensidadeHabito::Light,
                'status' => StatusPaciente::Active,
                'alergias' => ['Penicilina', 'Dipirona'],
                'condicoes' => ['Hipertensão Arterial'],
                'endereco' => [
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
                'sexo' => Sexo::Masculino,
                'tipo_sanguineo' => TipoSanguineo::OPositivo,
                'historico_tabagismo' => IntensidadeHabito::Moderate,
                'historico_alcool' => IntensidadeHabito::Moderate,
                'status' => StatusPaciente::Active,
                'alergias' => ['Sulfa'],
                'condicoes' => ['Diabetes Tipo 2', 'Hipertensão Arterial'],
                'endereco' => [
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
                'sexo' => Sexo::Feminino,
                'tipo_sanguineo' => TipoSanguineo::BNegativo,
                'historico_tabagismo' => IntensidadeHabito::None,
                'historico_alcool' => IntensidadeHabito::None,
                'status' => StatusPaciente::Active,
                'alergias' => [],
                'condicoes' => ['Asma'],
                'endereco' => null,
            ],
            [
                'nome' => 'Roberto Almeida Costa',
                'cpf' => '321.654.987-00',
                'telefone' => '(11) 97654-3210',
                'email' => 'roberto.costa@email.com',
                'data_nascimento' => '1965-01-30',
                'sexo' => Sexo::Masculino,
                'tipo_sanguineo' => TipoSanguineo::ABPositivo,
                'historico_tabagismo' => IntensidadeHabito::Intense,
                'historico_alcool' => IntensidadeHabito::Light,
                'status' => StatusPaciente::Inactive,
                'alergias' => ['AAS', 'Ibuprofeno'],
                'condicoes' => ['DPOC', 'Insuficiência Cardíaca'],
                'endereco' => [
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

        foreach ($pacientes as $dados) {
            $paciente = Paciente::query()->updateOrCreate(
                ['user_id' => $medico->id, 'cpf' => $dados['cpf']],
                [
                    'nome' => $dados['nome'],
                    'telefone' => $dados['telefone'],
                    'email' => $dados['email'],
                    'data_nascimento' => $dados['data_nascimento'],
                    'sexo' => $dados['sexo'],
                    'tipo_sanguineo' => $dados['tipo_sanguineo'],
                    'historico_tabagismo' => $dados['historico_tabagismo'],
                    'historico_alcool' => $dados['historico_alcool'],
                    'status' => $dados['status'],
                ],
            );

            if (! empty($dados['alergias'])) {
                $alergiaIds = collect($dados['alergias'])->map(
                    fn (string $nome) => Alergia::query()->firstOrCreate(['nome' => $nome])->id
                );
                $paciente->alergias()->sync($alergiaIds);
            }

            if (! empty($dados['condicoes'])) {
                $condicaoIds = collect($dados['condicoes'])->map(
                    fn (string $nome) => CondicaoCronica::query()->firstOrCreate(['nome' => $nome])->id
                );
                $paciente->condicoesCronicas()->sync($condicaoIds);
            }

            if ($dados['endereco']) {
                $paciente->endereco()->updateOrCreate(
                    ['paciente_id' => $paciente->id],
                    $dados['endereco'],
                );
            }
        }
    }
}
