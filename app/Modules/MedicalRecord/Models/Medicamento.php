<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\AnvisaList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nome
 * @property string $principio_ativo
 * @property string|null $apresentacao
 * @property string|null $fabricante
 * @property string|null $codigo_anvisa
 * @property AnvisaList|null $lista_anvisa
 * @property bool $controlado
 * @property bool $ativo
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Medicamento extends Model
{
    use HasFactory;

    protected $table = 'medicamentos';

    protected $fillable = [
        'nome',
        'principio_ativo',
        'apresentacao',
        'fabricante',
        'codigo_anvisa',
        'lista_anvisa',
        'controlado',
        'ativo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lista_anvisa' => AnvisaList::class,
            'controlado' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Medicamento>  $query
     * @return Builder<Medicamento>
     */
    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * @param  Builder<Medicamento>  $query
     * @return Builder<Medicamento>
     */
    public function scopeControlado(Builder $query): Builder
    {
        return $query->whereNotNull('lista_anvisa');
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicationFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicationFactory::new();
    }
}
