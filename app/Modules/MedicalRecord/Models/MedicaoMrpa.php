<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $resultado_mrpa_id
 * @property \Illuminate\Support\Carbon $data
 * @property string $hora
 * @property string $periodo
 * @property int $pas
 * @property int $pad
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read ResultadoMrpa $resultadoMrpa
 */
class MedicaoMrpa extends Model
{
    use HasFactory;

    protected $table = 'medicoes_mrpa';

    protected $fillable = [
        'resultado_mrpa_id',
        'data',
        'hora',
        'periodo',
        'pas',
        'pad',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'pas' => 'integer',
            'pad' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ResultadoMrpa, $this>
     */
    public function resultadoMrpa(): BelongsTo
    {
        return $this->belongsTo(ResultadoMrpa::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MrpaMeasurementFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MrpaMeasurementFactory::new();
    }
}
