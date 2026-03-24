<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $nome
 * @property string $corpo_template
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User|null $user
 */
class ModeloRelatorioMedico extends Model
{
    use HasFactory;

    protected $table = 'modelos_relatorio_medico';

    protected $fillable = [
        'user_id',
        'nome',
        'corpo_template',
    ];

    /**
     * @param  Builder<ModeloRelatorioMedico>  $query
     * @return Builder<ModeloRelatorioMedico>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->orWhereNull('user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicalReportTemplateFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicalReportTemplateFactory::new();
    }
}
