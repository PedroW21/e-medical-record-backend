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
 * @property string $padrao
 * @property string|null $texto_personalizado
 * @property int|null $anexo_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read Anexo|null $anexo
 */
class ResultadoEcg extends Model
{
    use HasFactory;

    protected $table = 'resultados_ecg';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'padrao',
        'texto_personalizado',
        'anexo_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
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

    /**
     * @return BelongsTo<Anexo, $this>
     */
    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class, 'anexo_id');
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\EcgResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\EcgResultFactory::new();
    }
}
