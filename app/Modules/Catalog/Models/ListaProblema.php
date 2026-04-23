<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\ListaProblemaFactory;
use App\Modules\Catalog\Enums\ProblemCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Predefined problem-list entry used as autocomplete for the
 * `prontuarios.lista_problemas` JSONB field.
 *
 * @property string $id
 * @property ProblemCategory $categoria
 * @property string $rotulo
 * @property array{id: string, label: string, options: array<int, string>}|null $variacao
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ListaProblema extends Model
{
    use HasFactory;

    protected $table = 'catalogo_lista_problemas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'categoria',
        'rotulo',
        'variacao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => ProblemCategory::class,
            'variacao' => 'array',
        ];
    }

    protected static function newFactory(): ListaProblemaFactory
    {
        return ListaProblemaFactory::new();
    }
}
