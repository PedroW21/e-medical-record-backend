<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $paciente_id
 * @property int $user_id
 * @property MedicalRecordType $tipo
 * @property MedicalRecordStatus $status
 * @property \Illuminate\Support\Carbon|null $finalizado_em
 * @property int|null $baseado_em_prontuario_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Paciente $paciente
 * @property-read User $user
 * @property-read Prontuario|null $prontuarioBase
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Prescricao> $prescricoes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ValorLaboratorial> $valoresLaboratoriais
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoEcg> $resultadosEcg
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoRx> $resultadosRx
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoTextoLivre> $resultadosTextoLivre
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RegistroTemperatura> $registrosTemperatura
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoElastografiaHepatica> $resultadosElastografiaHepatica
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoMapa> $resultadosMapa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoDexa> $resultadosDexa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoTesteErgometrico> $resultadosTesteErgometrico
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoEcodopplerCarotidas> $resultadosEcodopplerCarotidas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoEcocardiograma> $resultadosEcocardiograma
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoMrpa> $resultadosMrpa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoCat> $resultadosCat
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoCintilografia> $resultadosCintilografia
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ResultadoPeDiabetico> $resultadosPeDiabetico
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SolicitacaoExame> $solicitacoesExames
 */
class Prontuario extends Model
{
    use HasFactory;

    protected $table = 'prontuarios';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'tipo',
        'status',
        'finalizado_em',
        'baseado_em_prontuario_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => MedicalRecordType::class,
            'status' => MedicalRecordStatus::class,
            'finalizado_em' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === MedicalRecordStatus::Draft;
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuarioBase(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class, 'baseado_em_prontuario_id');
    }

    /**
     * @return HasMany<Prescricao, $this>
     */
    public function prescricoes(): HasMany
    {
        return $this->hasMany(Prescricao::class);
    }

    /**
     * @return HasMany<ValorLaboratorial, $this>
     */
    public function valoresLaboratoriais(): HasMany
    {
        return $this->hasMany(ValorLaboratorial::class);
    }

    /**
     * @return HasMany<ResultadoEcg, $this>
     */
    public function resultadosEcg(): HasMany
    {
        return $this->hasMany(ResultadoEcg::class);
    }

    /**
     * @return HasMany<ResultadoRx, $this>
     */
    public function resultadosRx(): HasMany
    {
        return $this->hasMany(ResultadoRx::class);
    }

    /**
     * @return HasMany<ResultadoTextoLivre, $this>
     */
    public function resultadosTextoLivre(): HasMany
    {
        return $this->hasMany(ResultadoTextoLivre::class);
    }

    /**
     * @return HasMany<RegistroTemperatura, $this>
     */
    public function registrosTemperatura(): HasMany
    {
        return $this->hasMany(RegistroTemperatura::class);
    }

    /**
     * @return HasMany<ResultadoElastografiaHepatica, $this>
     */
    public function resultadosElastografiaHepatica(): HasMany
    {
        return $this->hasMany(ResultadoElastografiaHepatica::class);
    }

    /**
     * @return HasMany<ResultadoMapa, $this>
     */
    public function resultadosMapa(): HasMany
    {
        return $this->hasMany(ResultadoMapa::class);
    }

    /**
     * @return HasMany<ResultadoDexa, $this>
     */
    public function resultadosDexa(): HasMany
    {
        return $this->hasMany(ResultadoDexa::class);
    }

    /**
     * @return HasMany<ResultadoTesteErgometrico, $this>
     */
    public function resultadosTesteErgometrico(): HasMany
    {
        return $this->hasMany(ResultadoTesteErgometrico::class);
    }

    /**
     * @return HasMany<ResultadoEcodopplerCarotidas, $this>
     */
    public function resultadosEcodopplerCarotidas(): HasMany
    {
        return $this->hasMany(ResultadoEcodopplerCarotidas::class);
    }

    /**
     * @return HasMany<ResultadoEcocardiograma, $this>
     */
    public function resultadosEcocardiograma(): HasMany
    {
        return $this->hasMany(ResultadoEcocardiograma::class);
    }

    /**
     * @return HasMany<ResultadoMrpa, $this>
     */
    public function resultadosMrpa(): HasMany
    {
        return $this->hasMany(ResultadoMrpa::class);
    }

    /**
     * @return HasMany<ResultadoCat, $this>
     */
    public function resultadosCat(): HasMany
    {
        return $this->hasMany(ResultadoCat::class);
    }

    /**
     * @return HasMany<ResultadoCintilografia, $this>
     */
    public function resultadosCintilografia(): HasMany
    {
        return $this->hasMany(ResultadoCintilografia::class);
    }

    /**
     * @return HasMany<ResultadoPeDiabetico, $this>
     */
    public function resultadosPeDiabetico(): HasMany
    {
        return $this->hasMany(ResultadoPeDiabetico::class);
    }

    /**
     * @return HasMany<SolicitacaoExame, $this>
     */
    public function solicitacoesExames(): HasMany
    {
        return $this->hasMany(SolicitacaoExame::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory::new();
    }
}
