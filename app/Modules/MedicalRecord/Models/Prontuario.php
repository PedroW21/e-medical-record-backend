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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory::new();
    }
}
