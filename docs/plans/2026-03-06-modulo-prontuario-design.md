# Modulo Prontuario — Design Document

**Data:** 2026-03-06
**Status:** Aprovado (pendente: metricas/evolucao)
**Revisado por:** 4 subagents (SQL Pro, Security Auditor, Fullstack Developer, Laravel Expert)

---

## 1. Visao Geral

Este documento descreve o design completo do modulo de Prontuario Medico (Medical Record), o ultimo grande modulo do backend. O frontend ja possui 100+ componentes com dados mock prontos para integracao.

### Escopo

- Prontuario (core + antropometria + JSONB sections)
- Prescricoes + logica ANVISA
- Resultados laboratoriais (normalizados)
- Resultados de exames estruturados (14 tabelas normalizadas)
- Solicitacoes de exames + catalogo TUSS
- Anexos + parsing IA assincrono
- Catalogos e seeders (lab, magistral, injetaveis, medicamentos, TUSS)
- Metricas/evolucao — **adiado para design separado**

### Principios

1. **Normalizar tudo que alimenta graficos de evolucao** — campos numericos clinicamente relevantes = colunas reais, nao JSONB
2. **JSONB para secoes consumidas como bloco** — exame fisico, lista de problemas, escores de risco, conduta
3. **Codigo em ingles, DB em portugues** — conforme CLAUDE.md
4. **Logica de negocio no backend** — frontend e consumidor, nao decisor
5. **Imutabilidade** — prontuarios finalizados nao podem ser editados

---

## 2. Arquitetura Modular

### Decisao: Dividir em sub-modulos (Review: Laravel Expert I1)

O escopo e grande demais para um unico modulo. Dividir em:

| Modulo | Responsabilidade | Tabelas |
|--------|-----------------|---------|
| `MedicalRecord` | Core prontuario + antropometria + JSONB sections + anexos | `prontuarios`, `medidas_antropometricas`, `anexos` |
| `Prescription` | Prescricoes + medicamentos + templates | `prescricoes`, `medicamentos`, `modelos_prescricao` |
| `ExamResult` | Todos os resultados de exames (lab + estruturados) | `valores_laboratoriais`, `resultados_*` (14 tabelas), catalogos lab |
| `ExamRequest` | Solicitacoes + modelos + TUSS | `solicitacoes_exames`, `modelos_solicitacao_exames`, `modelos_relatorio_medico`, `catalogo_exames_tuss` |
| `Catalog` | Catalogos globais (magistral, injetaveis, farmacias) | `catalogo_magistral_*`, `catalogo_injetaveis_*`, `catalogo_farmacias`, `catalogo_categorias_terapeuticas` |

Cada modulo segue a estrutura padrao: `Actions/`, `DTOs/`, `Services/`, `Http/Controllers/`, `Http/Requests/`, `Models/`, `Policies/`, `Providers/`, `Tests/`, `Database/`, `routes.php`.

---

## 3. Schema — Tabelas Core

### 3.1 `prontuarios`

```
id                          bigint PK
paciente_id                 bigint FK -> pacientes NOT NULL
user_id                     bigint FK -> users NOT NULL
tipo                        varchar NOT NULL
                            CHECK (tipo IN ('first_visit', 'follow_up', 'pre_anesthetic'))
status                      varchar NOT NULL DEFAULT 'draft'
                            CHECK (status IN ('draft', 'finalized'))
finalizado_em               timestamp nullable
baseado_em_prontuario_id    bigint nullable FK -> prontuarios ON DELETE SET NULL
exame_fisico                jsonb nullable
lista_problemas             jsonb nullable
escores_risco               jsonb nullable
conduta                     jsonb nullable
created_at, updated_at
```

**Indices:**
- `(paciente_id, created_at DESC)` — listagem por paciente
- `(user_id, paciente_id, created_at DESC)` — prontuarios do medico
- `(status, paciente_id)` — filtro draft/finalized

**Imutabilidade (Review: Security W01):**
- Eloquent `saving` event no model: throw exception se `finalizado_em IS NOT NULL`
- Trigger de DB como defense-in-depth:
  ```sql
  CREATE TRIGGER prevent_finalized_update
  BEFORE UPDATE ON prontuarios
  FOR EACH ROW WHEN (OLD.finalizado_em IS NOT NULL)
  EXECUTE FUNCTION raise_exception('Cannot modify finalized record');
  ```
- Mesma imutabilidade se propaga para tabelas filhas quando o prontuario pai esta finalizado

**JSONB Casts (Review: Laravel Expert R1):**
- Usar custom cast classes (`ExameFisicoCast`, `ListaProblemasCast`, `EscoresRiscoCast`, `CondutaCast`) em vez de cast generico `'array'`
- Cada cast retorna um DTO tipado, nao array raw
- Validacao de shape no Form Request com regras aninhadas

### 3.2 `medidas_antropometricas`

```
id                          bigint PK
prontuario_id               bigint FK -> prontuarios NOT NULL
paciente_id                 bigint FK -> pacientes NOT NULL (denorm)
-- Sinais vitais
peso                        decimal(6,2) nullable (kg)
altura                      decimal(5,2) nullable (cm)
imc                         decimal(5,2) nullable (kg/m2)
fc                          integer nullable (bpm)
spo2                        decimal(5,2) nullable (%)
temperatura                 decimal(4,2) nullable (C)
-- PA: 3 posicoes x 2 bracos
pa_sentado_d_pas             integer nullable
pa_sentado_d_pad             integer nullable
pa_sentado_e_pas             integer nullable
pa_sentado_e_pad             integer nullable
pa_em_pe_d_pas               integer nullable
pa_em_pe_d_pad               integer nullable
pa_em_pe_e_pas               integer nullable
pa_em_pe_e_pad               integer nullable
pa_deitado_d_pas             integer nullable
pa_deitado_d_pad             integer nullable
pa_deitado_e_pas             integer nullable
pa_deitado_e_pad             integer nullable
-- Circunferencias (cm)
circunferencia_pescoco       decimal(5,2) nullable
circunferencia_cintura       decimal(5,2) nullable
circunferencia_quadril       decimal(5,2) nullable
circunferencia_abdominal     decimal(5,2) nullable
circunferencia_braco_d       decimal(5,2) nullable
circunferencia_braco_e       decimal(5,2) nullable
circunferencia_coxa_d        decimal(5,2) nullable
circunferencia_coxa_e        decimal(5,2) nullable
circunferencia_panturrilha_d decimal(5,2) nullable
circunferencia_panturrilha_e decimal(5,2) nullable
-- Dobras cutaneas (mm)
dobra_tricipital             decimal(5,2) nullable
dobra_bicipital              decimal(5,2) nullable
dobra_subescapular           decimal(5,2) nullable
dobra_suprailica             decimal(5,2) nullable
dobra_abdominal              decimal(5,2) nullable
dobra_peitoral               decimal(5,2) nullable
dobra_coxa                   decimal(5,2) nullable
dobra_panturrilha            decimal(5,2) nullable
-- Avaliacao de via aerea (pre-anestesico)
abertura_bucal               decimal(4,2) nullable (cm)
distancia_tireomentual       decimal(4,2) nullable (cm)
distancia_mentoesternal      decimal(4,2) nullable (cm)
deslocamento_mandibular      varchar nullable
created_at, updated_at
```

**Indices:**
- `(paciente_id, created_at DESC)` — evolucao
- `(prontuario_id)` — busca por prontuario

---

## 4. Schema — Prescricoes

### 4.1 `prescricoes`

```
id                          bigint PK
prontuario_id               bigint FK -> prontuarios NOT NULL
subtipo                     varchar NOT NULL
                            CHECK (subtipo IN ('allopathic','magistral','injectable_im',
                              'injectable_ev','injectable_combined','injectable_protocol',
                              'glp1','steroid','subcutaneous_implant','ozonotherapy','procedure'))
tipo_receita                varchar NOT NULL DEFAULT 'normal'
                            CHECK (tipo_receita IN ('normal','white_c1','blue_b','yellow_a'))
tipo_receita_override       boolean DEFAULT false
itens                       jsonb NOT NULL
                            CHECK (jsonb_array_length(itens) > 0)
observacoes                 text nullable
impresso_em                 timestamp nullable
created_at, updated_at
```

**Indice:** `(prontuario_id, subtipo)`

**Enum Casts (Review: Laravel Expert R8):**
- `PrescriptionSubtype` enum (PHP backed enum, TitleCase cases em ingles)
- `PrescriptionType` enum (`Normal`, `WhiteC1`, `BlueB`, `YellowA`)

**Validacao JSONB por subtipo (Review: Laravel Expert I2):**
- `StorePrescriptionRequest` com regras dinamicas baseadas em `subtipo`
- Estrategia: switch no `rules()` chamando validators especificos por subtipo

### 4.2 `medicamentos` (catalogo global)

```
id                          bigint PK
nome                        varchar NOT NULL
principio_ativo             varchar NOT NULL
apresentacao                varchar nullable
fabricante                  varchar nullable
codigo_anvisa               varchar nullable
lista_anvisa                varchar nullable (A1,A2,A3,B1,B2,C1,C2,C3,C4,C5)
controlado                  boolean GENERATED ALWAYS AS (lista_anvisa IS NOT NULL) STORED
ativo                       boolean DEFAULT true
created_at, updated_at
```

**Nota (Review: SQL Pro C5):** `controlado` como generated column elimina risco de dessincronizacao.

**Indices:**
- `(lista_anvisa)` — filtro substancias controladas
- `(principio_ativo)` — busca por principio ativo
- `LOWER(nome)` — busca case-insensitive (functional index)

### 4.3 `modelos_prescricao` (por medico)

```
id                          bigint PK
user_id                     bigint FK -> users NOT NULL
nome                        varchar NOT NULL
tags                        jsonb nullable
subtipo                     varchar NOT NULL (enum)
itens                       jsonb NOT NULL
created_at, updated_at
```

### Logica Auto-Guess ANVISA (Portaria 344/98)

1. Para cada item com `medicamento_id` -> lookup `lista_anvisa`
2. Hierarquia: A1/A2/A3 -> `yellow_a` > B1/B2 -> `blue_b` > C1-C5 -> `white_c1` > null -> `normal`
3. Se `tipo_receita_override = true` -> manter escolha manual do medico
4. Magistral/Injetavel sem `medicamento_id` -> default `normal`, medico pode override
5. Procedures/Ozonotherapy/Implants -> sempre `normal`
6. Agrupamento: um registro de prescricao por `tipo_receita` por prontuario (sistema auto-agrupa itens pelo tipo mais restritivo)

---

## 5. Schema — Resultados Laboratoriais

### 5.1 `catalogo_exames_laboratoriais` (~254 analitos)

```
id                          varchar PK (ex: 'hemo-hemoglobina')
nome                        varchar NOT NULL
categoria                   varchar NOT NULL
unidade                     varchar NOT NULL
faixa_referencia            varchar nullable
tipo_resultado              varchar NOT NULL (numeric/qualitative/titer/descriptive)
avulso                      boolean DEFAULT false
criado_por_user_id          bigint nullable FK -> users
created_at, updated_at
```

**String PK** — match direto com IDs do frontend. Sem join de lookup.

**Nota (Review: Laravel Expert I5):** Models com string PK devem declarar `$incrementing = false` e `$keyType = 'string'`.

### 5.2 `paineis_laboratoriais` (~46 paineis)

```
id                          varchar PK
nome                        varchar NOT NULL
categoria                   varchar NOT NULL
subsecoes                   jsonb NOT NULL (array de {label, analytes[]})
created_at, updated_at
```

Predefinidos, nao editaveis pelo medico.

### 5.3 `valores_laboratoriais` (tabela de evolucao)

```
id                          bigint PK
prontuario_id               bigint FK -> prontuarios NOT NULL
paciente_id                 bigint FK -> pacientes NOT NULL (denorm)
catalogo_exame_id           varchar nullable FK -> catalogo_exames_laboratoriais
nome_avulso                 varchar nullable
unidade                     varchar NOT NULL
faixa_referencia            varchar nullable
painel_id                   varchar nullable (informativo)
data_coleta                 date NOT NULL
valor                       varchar NOT NULL
valor_numerico              decimal(12,4) nullable (auto-extraido)
created_at, updated_at

CHECK (catalogo_exame_id IS NOT NULL OR nome_avulso IS NOT NULL)
```

**Indices:**
- `(paciente_id, catalogo_exame_id, data_coleta)` — evolucao por analito
- `(paciente_id, nome_avulso, data_coleta)` — evolucao loose entries
- `(prontuario_id)` — listagem por prontuario

**Formato de entrada:** Frontend envia formato v1 flat (`LabExamValue[]`):
- Itens de painel tem `catalogId` preenchido
- Itens avulsos tem apenas `name`, sem `catalogId`

**Evolucao:** Exames do catalogo agrupam por `catalogo_exame_id`. Exames avulsos agrupam por `nome_avulso` (matching exato). Modulo de metricas resolve isso posteriormente.

---

## 6. Schema — Exames Estruturados (14 tabelas)

**Principio:** Todo campo numerico clinicamente relevante para tracking = coluna real. JSONB so para sub-estruturas consumidas como bloco.

**Padrao comum** — todas as tabelas possuem:
```
id              bigint PK
prontuario_id   bigint FK -> prontuarios NOT NULL
paciente_id     bigint FK -> pacientes NOT NULL (denorm)
data            date NOT NULL
created_at, updated_at
```

**Indices comuns:** `(paciente_id, data DESC)`, `(prontuario_id)`

### 6.1 `resultados_ecg`
- `padrao` varchar CHECK IN ('normal','right_deviation','left_deviation','altered')
- `texto_personalizado` text nullable

### 6.2 `resultados_rx`
- `padrao` varchar CHECK IN ('normal','poor_quality','altered')
- `texto_personalizado` text nullable

### 6.3 `resultados_ecocardiograma`
- `tipo` varchar (transthoracic/transesophageal)
- 25 colunas numericas: `raiz_aorta`, `aorta_ascendente`, `arco_aortico`, `ae_mm`, `ae_ml`, `ae_indexado`, `septo`, `dvd`, `ddve`, `dsve`, `pp`, `erp`, `indice_massa_ve`, `fe`, `psap`, `tapse`, `onda_e_mitral`, `onda_a`, `relacao_e_a`, `e_septal`, `e_lateral`, `relacao_e_e`, `s_tricuspide` — todas `decimal(8,2) nullable`
- `relacao_e_a_override` boolean DEFAULT false
- `valva_aortica`, `valva_mitral`, `valva_tricuspide` — `jsonb nullable` ({status, description})
- `analise_qualitativa` text nullable

### 6.4 `resultados_mapa`
- `pas_vigilia`, `pad_vigilia`, `pas_sono`, `pad_sono` — `decimal(6,2) nullable`
- `pas_24h`, `pad_24h` — `decimal(6,2) nullable` (auto-calculado)
- `pas_24h_override`, `pad_24h_override` — boolean DEFAULT false
- `descenso_noturno_pas`, `descenso_noturno_pad` — `decimal(6,2) nullable` (%)
- `descenso_noturno_pas_override`, `descenso_noturno_pad_override` — boolean DEFAULT false
- `observacoes` text nullable

### 6.5 `resultados_mrpa` (pai)
- `dias_monitorados` integer
- `membro` varchar (right_arm/left_arm)
- `observacoes` text nullable

#### `medicoes_mrpa` (filho — time-series)
```
id                  bigint PK
resultado_mrpa_id   bigint FK -> resultados_mrpa NOT NULL
data                date NOT NULL
hora                time NOT NULL
periodo             varchar NOT NULL (morning/evening)
pas                 integer NOT NULL (mmHg)
pad                 integer NOT NULL (mmHg)
```
**Indice:** `(resultado_mrpa_id, data, hora)`

### 6.6 `resultados_dexa`
- 14 colunas numericas: `peso_total`, `dmo`, `t_score`, `gordura_corporal_pct`, `gordura_total`, `imc`, `gordura_visceral`, `gordura_visceral_pct`, `massa_magra`, `massa_magra_pct`, `fmi`, `ffmi`, `rsmi`, `tmr` — todas `decimal(10,4) nullable`

### 6.7 `resultados_teste_ergometrico`
- `protocolo` varchar nullable (bruce/bruce_modified/ellestad/naughton/balke/ramp)
- 12 colunas numericas: `pct_fc_max_prevista`, `fc_max`, `pas_max`, `pas_pre`, `vo2_max`, `mvo2_max`, `deficit_cronotropico`, `deficit_funcional_ve`, `debito_cardiaco`, `volume_sistolico`, `dp_max`, `met_max`
- `aptidao_cardiorrespiratoria` varchar nullable (low/moderate/excellent)
- `observacoes` text nullable

### 6.8 `resultados_ecodoppler_carotidas`
- 16 colunas numericas (8 arterias x 2 medicoes): `carotida_comum_e_espessura_intimal`, `carotida_comum_e_grau_estenose`, `carotida_comum_d_espessura_intimal`, `carotida_comum_d_grau_estenose`, `carotida_externa_e_*`, `carotida_externa_d_*`, `bulbo_interna_e_*`, `bulbo_interna_d_*`, `vertebral_e_*`, `vertebral_d_*` — todas `decimal(6,2) nullable`
- `observacoes` text nullable

### 6.9 `resultados_elastografia_hepatica`
- `fracao_gordura`, `tsi`, `kpa` — `decimal(8,4) nullable`
- `observacoes` text nullable

### 6.10 `resultados_cat`
- 9 colunas JSONB (uma por arteria): `cd`, `ce`, `da`, `cx`, `d1`, `d2`, `mge`, `mgd`, `dp`
  - Cada uma: `{status: 'pervia'|'obstrucao'|null, proximal: {has_obstruction, percentage}, media: {...}, distal: {...}}`
- `stents` jsonb (array de {artery, status})
- `observacoes` text nullable

### 6.11 `resultados_cintilografia`
- ~30 colunas (ver memoria 08 para lista completa)
- Scores: `sss`, `srs`, `sds` (integer nullable), overrides
- Funcao ventricular: `fe_repouso`, `vdf_repouso`, `vsf_repouso`, `fe_estresse`, `vdf_estresse`, `vsf_estresse` — decimal
- Perfusao por territorio (DA, CX, CD): 9 colunas varchar (stress, rest, reversibility por territorio)
- `segmentos` jsonb nullable (17 segmentos bull's eye — consumido como bloco para diagrama)
- `sintomas_estresse`, `alteracoes_ecg_estresse` — jsonb (arrays de strings)

### 6.12 `resultados_pe_diabetico`
- ~80+ colunas (a detalhar na implementacao baseado no frontend `DiabeticFootScreeningResult`)
- Blocos: anamnese, NSS, monofilamento (4 sitios x 2 pes), PSP/IPTT, diapasao, VPT, NDS, vascular, ITB/TBI, Doppler, termometria, inspecao visual (pele + unhas), deformidades, ulcera (Wagner + SINBAD), classificacao IWGDF
- Todos os scores numericos (NSS, NDS, ITB, TBI, SINBAD) como colunas reais com `_override` boolean
- **Acao (Review: Fullstack):** Detalhar todos os ~80 campos na migration baseado no tipo frontend `DiabeticFootScreeningResult`

### 6.13 `registros_temperatura`
- `hora` time NOT NULL
- `valor` decimal(4,2) NOT NULL (C)

### 6.14 `resultados_texto_livre`
- `tipo` varchar NOT NULL (holter/polysomnography/other)
- `texto` text NOT NULL

---

## 7. Schema — Solicitacoes de Exames

### 7.1 `catalogo_exames_tuss`

```
id                          bigint PK
codigo_tuss                 varchar NOT NULL UNIQUE
nome                        varchar NOT NULL
categoria                   varchar nullable
subcategoria                varchar nullable
ativo                       boolean DEFAULT true
created_at, updated_at
```

Populado via seeder (tabela TUSS publica).

### 7.2 `solicitacoes_exames`

```
id                          bigint PK
prontuario_id               bigint FK -> prontuarios NOT NULL
modelo_id                   bigint nullable FK -> modelos_solicitacao_exames ON DELETE SET NULL
cid_10                      varchar nullable
indicacao_clinica           text nullable
itens                       jsonb NOT NULL
                            CHECK (jsonb_array_length(itens) > 0)
relatorio_medico            jsonb nullable ({template_id?, body})
impresso_em                 timestamp nullable
created_at, updated_at
```

**Nota (Review: SQL Pro W3):** `modelo_id` corrigido para `bigint FK` com `SET NULL ON DELETE`.

**Indice:** `(prontuario_id)`

### 7.3 `modelos_solicitacao_exames` (por medico)

```
id                          bigint PK
user_id                     bigint FK -> users NOT NULL
nome                        varchar NOT NULL
categoria                   varchar nullable
itens                       jsonb NOT NULL
created_at, updated_at
```

### 7.4 `modelos_relatorio_medico` (por medico)

```
id                          bigint PK
user_id                     bigint FK -> users NOT NULL
nome                        varchar NOT NULL
corpo_template              text NOT NULL (suporta {{CID_10}})
created_at, updated_at
```

---

## 8. Schema — Anexos

### 8.1 `anexos`

```
id                          bigint PK
prontuario_id               bigint FK -> prontuarios NOT NULL
paciente_id                 bigint FK -> pacientes NOT NULL (denorm)
tipo_anexo                  varchar NOT NULL
                            CHECK (tipo_anexo IN ('lab','ecg','rx','eco','mapa','mrpa','dexa',
                              'teste_ergometrico','ecodoppler_carotidas','elastografia_hepatica',
                              'cat','cintilografia','pe_diabetico','holter','polissonografia',
                              'documento','outro'))
nome                        varchar NOT NULL
tipo_arquivo                varchar NOT NULL (pdf/png/jpg)
caminho                     varchar NOT NULL (path no disco local — NUNCA exposto na API)
tamanho_bytes               bigint NOT NULL
status_processamento        varchar nullable DEFAULT 'not_applicable'
                            CHECK (status_processamento IN ('not_applicable','pending','processing','completed','failed'))
dados_extraidos             jsonb nullable
created_at, updated_at
```

**Nota (Review: Laravel Expert I7):** Substituido `null` por `not_applicable` para evitar ambiguidade semantica.

**Indices:**
- `(prontuario_id)`
- `(paciente_id, tipo_anexo)`
- `(status_processamento)` — polling do job assincrono

### Fluxo de Upload (Review: Security C03, Laravel Expert R4)

1. **Upload:** `Storage::disk('local')->putFile('anexos/'.$prontuarioId, $file)` — UUID auto-gerado, NUNCA usa filename original
2. **Validacao:** Form Request com `'arquivo' => ['required', 'file', 'mimes:pdf,jpg,png', 'max:20480']` + validacao MIME server-side via `finfo`
3. **Registro:** Cria registro com `status_processamento`:
   - `documento`/`outro` -> `not_applicable`
   - Tipos de exame -> `pending`
4. **Job assincrono:** Dispatcha `ParseAttachmentJob`
5. **Download:** Endpoint dedicado `AttachmentController@download` que verifica autorizacao e faz `Storage::download()`. **NUNCA** expor `caminho` na API.

### ParseAttachmentJob (Review: Laravel Expert R5, Security C04)

```php
public int $tries = 3;
public int $timeout = 120;
public array $backoff = [30, 60, 120];

// ShouldBeUnique com key = Anexo::id
```

- `failed()` method: atualiza `status_processamento = 'failed'`
- **Seguranca IA (Review: Security C04):**
  - Strip PII (nome paciente, CPF, data nascimento) antes de enviar ao provider
  - Usar provider com zero-data-retention (Azure OpenAI com opt-out, ou self-hosted)
  - Logar cada chamada AI no audit trail
  - Validar `dados_extraidos` contra schema esperado antes de salvar

---

## 9. Schema — Catalogos

### 9.1 Magistral

#### `catalogo_magistral_categorias`
```
id          varchar PK
label       varchar NOT NULL
icone       varchar nullable
grupo       varchar NOT NULL (farmaco/alvo)
ordem       integer DEFAULT 0
```

#### `catalogo_magistral_formulas`
```
id              varchar PK
categoria_id    varchar FK -> catalogo_magistral_categorias (string FK explicita)
nome            varchar NOT NULL
componentes     jsonb NOT NULL ([{name, dose}])
excipiente      text nullable
posologia       text NOT NULL
instrucoes      text nullable
notas           text nullable
```

### 9.2 Injetaveis

#### `catalogo_farmacias`
```
id      varchar PK (victa/pineda/healthtech)
label   varchar NOT NULL
cor     varchar nullable
```

#### `catalogo_categorias_terapeuticas`
```
id      varchar PK (~26 categorias)
label   varchar NOT NULL
icone   varchar nullable
```

#### `catalogo_injetaveis_farmacos`
```
id              varchar PK
nome            varchar NOT NULL
dosagem         varchar NOT NULL
volume          varchar nullable
vias_permitidas jsonb NOT NULL (array de strings: im/ev/sc/id/ia/...)
farmacia_id     varchar FK -> catalogo_farmacias (string FK explicita)
via_exclusiva   varchar nullable
is_blend        boolean DEFAULT false
composicao      text nullable
```

#### `catalogo_injetaveis_protocolos`
```
id                          varchar PK
nome                        varchar NOT NULL
farmacia_id                 varchar FK -> catalogo_farmacias
via                         varchar NOT NULL (im/ev/combined)
categoria_terapeutica_id    varchar FK -> catalogo_categorias_terapeuticas
componentes                 jsonb NOT NULL ([{farmacoName, volume, quantity?}])
notas_aplicacao             text nullable
```

**Nota (Review: SQL Pro W6):** Todas as FKs de catalogo com string PK devem usar `$table->string('campo')->references('id')->on('tabela')` nas migrations. NUNCA usar `foreignId()`.

---

## 10. Seguranca e Compliance

### 10.1 Audit Trail (Review: Security C01) — CRITICO

**Implementar ANTES da Phase 1.** Todo acesso e mutacao de prontuarios e tabelas filhas deve ser logado.

```
auditoria
├── id              bigint PK
├── user_id         bigint FK -> users
├── acao            varchar NOT NULL (view/create/update/finalize/delete)
├── tipo_recurso    varchar NOT NULL (prontuario/prescricao/resultado_lab/...)
├── recurso_id      bigint NOT NULL
├── ip_address      varchar nullable
├── valores_antigos jsonb nullable
├── valores_novos   jsonb nullable
├── created_at      timestamp NOT NULL
```

- Append-only (sem UPDATE/DELETE para o usuario da aplicacao)
- Considerar `spatie/laravel-activitylog` ou implementacao custom

### 10.2 Encriptacao (Review: Security C02)

- Avaliar `encrypted` cast no campo `cpf` do model `Paciente`
- Se encriptar CPF: criar blind index (HMAC hash) para buscas
- Habilitar PostgreSQL TDE ou `pgcrypto` para protecao a nivel de DB
- Campos JSONB sensiveis (`lista_problemas`, `conduta`) — avaliar `encrypted:array` cast

### 10.3 Autorizacao (Review: Laravel Expert R3, R7)

- `MedicalRecordPolicy` como spine de autorizacao
- Toda query de prontuario scoped por `prontuarios -> pacientes.user_id = $userId`
- Delegation scope: definir explicitamente o que secretarias podem acessar (metadata vs conteudo clinico)
- Defense in depth: scoping no service layer + Gate::authorize() no controller

### 10.4 Rate Limiting (Review: Security W04)

- `throttle:api` global em `bootstrap/app.php`
- Limites mais restritos para: upload de arquivos, trigger de parsing IA, listagem de pacientes

### 10.5 LGPD (Review: Security W05, W06)

- Retencao: CFM exige minimo 20 anos. Documentar base legal para retencao alem de pedidos de exclusao LGPD (Art. 16(I))
- Consentimento para processamento IA: flag por paciente
- Endpoint de exportacao de dados para portabilidade (Art. 18(V))

---

## 11. Denormalizacao: Regras de Integridade

### `paciente_id` denormalizado (Review: SQL Pro C2)

Todas as tabelas com dual FK (`prontuario_id` + `paciente_id`) devem:

1. **NUNCA** aceitar `paciente_id` do input do usuario
2. **SEMPRE** derivar de `Prontuario::find($prontuarioId)->paciente_id` no service/action layer
3. Ter teste automatizado validando essa regra
4. Tabelas afetadas: `medidas_antropometricas`, `valores_laboratoriais`, todas as `resultados_*`, `anexos`

---

## 12. Mapeamento Frontend <-> Backend (API Resources)

O frontend usa nomes em ingles, o backend armazena em portugues. Os API Resources fazem o mapeamento.

Exemplos de mapeamentos criticos:

| Frontend field | Backend column | Tabela |
|---|---|---|
| `ef` | `fe` | resultados_ecocardiograma |
| `hr_max` | `fc_max` | resultados_teste_ergometrico |
| `systolic` / `diastolic` | `pas` / `pad` | medicoes_mrpa |
| `limb` | `membro` | resultados_mrpa |
| `notes` | `observacoes` | resultados_mapa |
| `rmr` | `tmr` | resultados_dexa |
| `file_url` | URL gerada do `caminho` | anexos |
| `category` | `tipo_anexo` | anexos |
| `uploaded_at` | `created_at` | anexos |

**Todos os mapeamentos EN<->PT sao responsabilidade dos API Resources. Os controllers NUNCA transformam nomes de campo.**

---

## 13. Seeders

| Seeder | Fonte | Qtd | Estrategia |
|--------|-------|-----|-----------|
| `LabCatalogSeeder` | frontend `lab-catalog-data.ts` | ~254 | `upsert()` chunked |
| `LabPanelSeeder` | frontend `lab-panel-definitions.ts` | ~46 | `upsert()` chunked |
| `TussCatalogSeeder` | Tabela TUSS publica | TBD | `upsert()` chunked |
| `MedicationSeeder` | Dados ANVISA | TBD | `upsert()` chunked |
| `ExamModelSeeder` | frontend `exam-models.ts` | 7 | `firstOrCreate()` |
| `MagistralCatalogSeeder` | frontend `magistralCatalogData.ts` | ~855 | `upsert()` chunked |
| `InjectableCatalogSeeder` | frontend `injectableCatalogData.ts` + `injectable/` | ~300+ | `upsert()` chunked |
| `ProblemListSeeder` | frontend `problem-list-defaults.ts` | ~35 | JSONB defaults |

**Nota (Review: Laravel Expert I6, R6):** Seeders com alto volume usam `Model::query()->upsert()` com chunks de 100. Idempotentes para CI.

---

## 14. Fases de Implementacao

| Fase | Escopo | Dependencias |
|------|--------|-------------|
| 0 | Audit trail + infraestrutura de seguranca | Nenhuma |
| 1 | Core prontuario + antropometria + JSONB sections | Fase 0 |
| 2 | Prescricoes + medicamentos + ANVISA auto-guess | Fase 1 |
| 3 | Lab results (catalogo + paineis + valores) | Fase 1 |
| 4 | Exames estruturados (14 tabelas) | Fase 1 |
| 5 | Solicitacoes de exames + TUSS | Fase 1 |
| 6 | Anexos + parsing IA | Fase 1 |
| 7 | Catalogos (magistral + injetaveis) | Nenhuma |
| 8 | Metricas / evolucao API | Fases 1-4 |

Fases 2-7 podem ser paralelizadas apos Fase 1.

---

## 15. Decisoes de Review Incorporadas

### Do SQL Pro
- [x] CHECK constraints em todos os enums (tipo, status, subtipo, tipo_receita, padrao, tipo_anexo, status_processamento)
- [x] `baseado_em_prontuario_id` com `ON DELETE SET NULL`
- [x] `medicamentos.controlado` como generated column
- [x] `modelo_id` em `solicitacoes_exames` corrigido para bigint FK
- [x] Indices documentados para todas as tabelas
- [x] `decimal(X,Y)` com escala explicita em vez de bare `decimal`
- [ ] CPF unique global — avaliar impacto (out of scope, modulo Patient existente)

### Do Security Auditor
- [x] Fase 0 de audit trail adicionada
- [x] File upload hardening documentado
- [x] AI data exposure controls documentados
- [x] Immutability enforcement (model + trigger)
- [ ] Encriptacao at rest — avaliar na implementacao
- [ ] LGPD consent management — avaliar na implementacao

### Do Fullstack Developer
- [x] Mapeamento EN<->PT documentado (secao 12)
- [x] Campos de via aerea adicionados em medidas_antropometricas
- [x] Panturrilhas adicionadas em circunferencias
- [x] Pe diabetico: ~80 campos a detalhar na migration (marcado como acao)
- [x] `status_processamento` com `not_applicable` em vez de null
- [ ] Documentar shapes JSONB de conduta, escores_risco — na implementacao

### Do Laravel Expert
- [x] Split em sub-modulos (secao 2)
- [x] Custom casts para JSONB (secao 3.1)
- [x] Validacao JSONB por subtipo (secao 4.1)
- [x] ParseAttachmentJob retry policy (secao 8)
- [x] String PK: $incrementing=false, $keyType='string'
- [x] Seeders com upsert() chunked (secao 13)
- [x] Enum casts para subtipo/tipo_receita
