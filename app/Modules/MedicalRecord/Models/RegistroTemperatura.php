<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property int $paciente_id
 * @property \Illuminate\Support\Carbon $data
 * @property string $hora
 * @property float $valor
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class RegistroTemperatura extends Model
{
    use HasFactory;

    protected $table = 'registros_temperatura';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'hora',
        'valor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'valor' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\TemperatureRecordFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\TemperatureRecordFactory::new();
    }
}
