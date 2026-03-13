<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\LabCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $nome
 * @property LabCategory $categoria
 * @property array<int, array{label: string, analytes: array<int, array<string, mixed>>}> $subsecoes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PainelLaboratorial extends Model
{
    protected $table = 'paineis_laboratoriais';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nome',
        'categoria',
        'subsecoes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => LabCategory::class,
            'subsecoes' => 'array',
        ];
    }
}
