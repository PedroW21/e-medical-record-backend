# Design — Módulo de Pacientes (Backend)

**Data:** 2026-02-16
**Status:** Aprovado

## Contexto

O frontend já possui o módulo de pacientes completo (CRUD, listagem, formulários, busca CEP) usando dados mock. Este design cobre a implementação do backend para substituir os mocks por uma API real.

## Decisões

| Decisão | Escolha |
|---------|---------|
| Escopo de propriedade | Paciente pertence a um médico (`user_id`) |
| Exclusão | Soft delete (`deleted_at`) |
| Paginação | Offset-based (`page` / `per_page`) |
| Endereço | Tabela separada (`enderecos`) com relação 1:1 |
| CPF | Único por médico (unique constraint `user_id` + `cpf`) |
| Alergias / Condições crônicas | Tabelas normalizadas com pivot many-to-many |
| Busca CEP | Endpoint backend que faz proxy para ViaCEP |
| Permissões | Médicos: CRUD completo. Secretárias: acesso futuro respeitando sigilo médico-paciente |
| Mapeamento API | Banco em PT → API em EN (alinhado com tipos TypeScript do frontend) |

## Schema do Banco de Dados

### Tabela `alergias`

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `id` | bigint | PK auto-increment |
| `nome` | varchar(255) | unique |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Tabela `condicoes_cronicas`

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `id` | bigint | PK auto-increment |
| `nome` | varchar(255) | unique |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Tabela `pacientes`

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `id` | bigint | PK auto-increment |
| `user_id` | bigint | FK → users.id, NOT NULL |
| `nome` | varchar(255) | NOT NULL |
| `cpf` | varchar(14) | NOT NULL |
| `telefone` | varchar(20) | NOT NULL |
| `email` | varchar(255) | nullable |
| `data_nascimento` | date | NOT NULL |
| `sexo` | varchar(10) | NOT NULL (masculino/feminino) |
| `tipo_sanguineo` | varchar(5) | nullable |
| `historico_tabagismo` | varchar(20) | nullable (none/light/moderate/intense) |
| `historico_alcool` | varchar(20) | nullable (none/light/moderate/intense) |
| `status` | varchar(10) | NOT NULL, default 'active' |
| `ultima_consulta` | timestamp | nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | nullable (soft delete) |

**Unique constraint:** `(user_id, cpf)` — excluindo soft-deleted.

### Tabela `enderecos`

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `id` | bigint | PK auto-increment |
| `paciente_id` | bigint | FK → pacientes.id, unique |
| `cep` | varchar(10) | NOT NULL |
| `logradouro` | varchar(255) | NOT NULL |
| `numero` | varchar(20) | NOT NULL |
| `complemento` | varchar(255) | nullable |
| `bairro` | varchar(255) | NOT NULL |
| `cidade` | varchar(255) | NOT NULL |
| `estado` | varchar(2) | NOT NULL |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Tabela `alergia_paciente` (pivot)

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `paciente_id` | bigint | FK → pacientes.id |
| `alergia_id` | bigint | FK → alergias.id |

**PK composta:** `(paciente_id, alergia_id)`

### Tabela `condicao_cronica_paciente` (pivot)

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `paciente_id` | bigint | FK → pacientes.id |
| `condicao_cronica_id` | bigint | FK → condicoes_cronicas.id |

**PK composta:** `(paciente_id, condicao_cronica_id)`

## Endpoints da API

Todos autenticados via `auth:sanctum`.

### Pacientes

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/pacientes` | Listar pacientes do médico (paginado) |
| POST | `/api/pacientes` | Criar paciente |
| GET | `/api/pacientes/{id}` | Detalhe do paciente |
| PUT | `/api/pacientes/{id}` | Atualizar paciente |
| DELETE | `/api/pacientes/{id}` | Soft delete (só médico dono) |

**Query params (listagem):**
- `page` (int) — página atual
- `per_page` (int, max 100, default 15) — itens por página
- `busca` (string) — busca por nome ou CPF
- `status` (string) — filtro active/inactive

### Alergias e Condições Crônicas (autocomplete)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/alergias` | Listar alergias (com busca por `?busca=`) |
| GET | `/api/condicoes-cronicas` | Listar condições crônicas (com busca por `?busca=`) |

### Endereço (CEP)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/enderecos/cep/{cep}` | Busca endereço via ViaCEP |

Retorna no mesmo formato que o backend espera no CRUD de pacientes:

```json
{
  "data": {
    "cep": "04101-000",
    "logradouro": "Rua das Flores",
    "bairro": "Vila Mariana",
    "cidade": "São Paulo",
    "estado": "SP"
  }
}
```

## Mapeamento API (Banco PT → Response EN)

O `PacienteResource` traduz os campos para alinhar com os tipos TypeScript do frontend:

```
Banco (PT)              → API (EN)
─────────────────────────────────────
nome                    → name
cpf                     → cpf
telefone                → phone
email                   → email
data_nascimento         → birth_date
sexo                    → gender (masculino→male, feminino→female)
tipo_sanguineo          → blood_type
alergias (relação)      → allergies (array de strings com nomes)
condicoes_cronicas      → chronic_conditions (array de strings)
historico_tabagismo     → medical_history.smoking
historico_alcool        → medical_history.alcohol
ultima_consulta         → last_visit
status                  → status
endereco.cep            → address.cep
endereco.logradouro     → address.street
endereco.numero         → address.number
endereco.complemento    → address.complement
endereco.bairro         → address.neighborhood
endereco.cidade         → address.city
endereco.estado         → address.state
```

Os requests (create/update) aceitam os mesmos nomes EN do frontend. A conversão EN→PT acontece nos DTOs.

## Estrutura do Módulo

```
app/Modules/Paciente/
├── Actions/
│   ├── CreatePacienteAction.php
│   ├── UpdatePacienteAction.php
│   └── DeletePacienteAction.php
├── Database/
│   ├── Factories/
│   │   ├── PacienteFactory.php
│   │   ├── EnderecoFactory.php
│   │   ├── AlergiaFactory.php
│   │   └── CondicaoCronicaFactory.php
│   ├── Migrations/ (6 migrations, ordenadas)
│   └── Seeders/
│       ├── AlergiaSeeder.php
│       ├── CondicaoCronicaSeeder.php
│       └── PacienteSeeder.php
├── DTOs/
│   ├── CreatePacienteDTO.php
│   ├── UpdatePacienteDTO.php
│   └── EnderecoDTO.php
├── Enums/
│   ├── IntensidadeHabito.php
│   ├── Sexo.php
│   ├── TipoSanguineo.php
│   └── StatusPaciente.php
├── Http/
│   ├── Controllers/
│   │   ├── PacienteController.php
│   │   ├── AlergiaController.php
│   │   ├── CondicaoCronicaController.php
│   │   └── EnderecoController.php
│   ├── Requests/
│   │   ├── StorePacienteRequest.php
│   │   ├── UpdatePacienteRequest.php
│   │   └── ListPacienteRequest.php
│   └── Resources/
│       ├── PacienteResource.php
│       ├── PacienteListResource.php
│       ├── EnderecoResource.php
│       ├── AlergiaResource.php
│       └── CondicaoCronicaResource.php
├── Models/
│   ├── Paciente.php
│   ├── Endereco.php
│   ├── Alergia.php
│   └── CondicaoCronica.php
├── Policies/
│   └── PacientePolicy.php
├── Providers/
│   └── PacienteServiceProvider.php
├── Services/
│   ├── PacienteService.php
│   └── CepService.php
├── Tests/Feature/
│   ├── ListPacienteTest.php
│   ├── CreatePacienteTest.php
│   ├── ShowPacienteTest.php
│   ├── UpdatePacienteTest.php
│   ├── DeletePacienteTest.php
│   └── BuscaCepTest.php
└── routes.php
```

## Autorização (Policy)

- `viewAny`, `view`, `create`, `update`: Médico dono (por enquanto só médicos; secretárias no futuro)
- `delete`: Apenas médico dono
- Scope automático: pacientes são sempre filtrados por `user_id` do autenticado

## Seeders

- `AlergiaSeeder`: Lista comum de alergias (Penicilina, Dipirona, AAS, Sulfa, Latex, etc.)
- `CondicaoCronicaSeeder`: Lista comum de condições (Hipertensão Arterial, Diabetes Tipo 2, Asma, DPOC, etc.)
- `PacienteSeeder`: Pacientes de exemplo para os médicos de teste (Glayson e Pedro Verner)
