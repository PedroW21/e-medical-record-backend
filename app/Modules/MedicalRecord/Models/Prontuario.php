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
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $paciente_id
 * @property int $user_id
 * @property MedicalRecordType $tipo
 * @property MedicalRecordStatus $status
 * @property \Illuminate\Support\Carbon|null $finalizado_em
 * @property int|null $baseado_em_prontuario_id
 * @property \App\Modules\MedicalRecord\DTOs\PhysicalExamData|null $exame_fisico
 * @property \App\Modules\MedicalRecord\DTOs\ProblemListData|null $lista_problemas
 * @property \App\Modules\MedicalRecord\DTOs\RiskScoresData|null $escores_risco
 * @property \App\Modules\MedicalRecord\DTOs\ConductData|null $conduta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Paciente $paciente
 * @property-read User $user
 * @property-read Prontuario|null $prontuarioBase
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Prescricao> $prescricoes
 * @property-read MedidaAntropometrica|null $medidaAntropometrica
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
        'exame_fisico',
        'lista_problemas',
        'escores_risco',
        'conduta',
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
            'exame_fisico' => \App\Modules\MedicalRecord\Casts\PhysicalExamCast::class,
            'lista_problemas' => \App\Modules\MedicalRecord\Casts\ProblemListCast::class,
            'escores_risco' => \App\Modules\MedicalRecord\Casts\RiskScoresCast::class,
            'conduta' => \App\Modules\MedicalRecord\Casts\ConductCast::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Prontuario $prontuario): void {
            if (! $prontuario->isDirty('finalizado_em') && $prontuario->getOriginal('finalizado_em') !== null) {
                throw new \Illuminate\Validation\ValidationException(
                    validator: validator([], []),
                    response: response()->json([
                        'message' => 'Não é possível modificar um prontuário finalizado.',
                    ], 403)
                );
            }
        });
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
     * @return HasOne<MedidaAntropometrica, $this>
     */
    public function medidaAntropometrica(): HasOne
    {
        return $this->hasOne(MedidaAntropometrica::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory::new();
    }
}
