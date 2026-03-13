<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\LabCategory;
use App\Modules\MedicalRecord\Enums\LabResultType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $nome
 * @property LabCategory $categoria
 * @property string $unidade
 * @property string|null $faixa_referencia
 * @property LabResultType $tipo_resultado
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class CatalogoExameLaboratorial extends Model
{
    protected $table = 'catalogo_exames_laboratoriais';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nome',
        'categoria',
        'unidade',
        'faixa_referencia',
        'tipo_resultado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => LabCategory::class,
            'tipo_resultado' => LabResultType::class,
        ];
    }
}
