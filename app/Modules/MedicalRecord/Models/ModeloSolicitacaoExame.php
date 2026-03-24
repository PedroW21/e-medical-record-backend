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
 * @property string|null $categoria
 * @property array<int, array<string, mixed>> $itens
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User|null $user
 */
class ModeloSolicitacaoExame extends Model
{
    use HasFactory;

    protected $table = 'modelos_solicitacao_exames';

    protected $fillable = [
        'user_id',
        'nome',
        'categoria',
        'itens',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'itens' => 'array',
        ];
    }

    /**
     * @param  Builder<ModeloSolicitacaoExame>  $query
     * @return Builder<ModeloSolicitacaoExame>
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\ExamRequestModelFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\ExamRequestModelFactory::new();
    }
}
