<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $nome
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Paciente> $pacientes
 */
class Alergia extends Model
{
    use HasFactory;

    protected $table = 'alergias';

    protected $fillable = [
        'nome',
    ];

    /**
     * @return BelongsToMany<Paciente, $this>
     */
    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(Paciente::class, 'alergia_paciente');
    }

    protected static function newFactory(): \App\Modules\Paciente\Database\Factories\AlergiaFactory
    {
        return \App\Modules\Paciente\Database\Factories\AlergiaFactory::new();
    }
}
