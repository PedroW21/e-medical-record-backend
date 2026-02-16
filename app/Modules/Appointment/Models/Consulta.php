<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Models;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $paciente_id
 * @property string $data
 * @property string $horario
 * @property AppointmentType $tipo
 * @property AppointmentStatus $status
 * @property string|null $observacoes
 * @property string|null $nome_solicitante
 * @property string|null $telefone_solicitante
 * @property string|null $email_solicitante
 * @property AppointmentOrigin $origem
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Paciente|null $paciente
 */
class Consulta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultas';

    protected $fillable = [
        'user_id',
        'paciente_id',
        'data',
        'horario',
        'tipo',
        'status',
        'observacoes',
        'nome_solicitante',
        'telefone_solicitante',
        'email_solicitante',
        'origem',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'origem' => AppointmentOrigin::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    protected static function newFactory(): \App\Modules\Appointment\Database\Factories\AppointmentFactory
    {
        return \App\Modules\Appointment\Database\Factories\AppointmentFactory::new();
    }
}
