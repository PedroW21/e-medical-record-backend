<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Models;

use App\Models\User;
use App\Modules\Appointment\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Schedule settings block for a doctor.
 *
 * @property int $id
 * @property int $user_id
 * @property DayOfWeek $dia_semana
 * @property string $hora_inicio
 * @property string $hora_fim
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class HorarioAtendimento extends Model
{
    use HasFactory;

    protected $table = 'horarios_atendimento';

    protected $fillable = [
        'user_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia_semana' => DayOfWeek::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): \App\Modules\Appointment\Database\Factories\ScheduleSettingsFactory
    {
        return \App\Modules\Appointment\Database\Factories\ScheduleSettingsFactory::new();
    }
}
