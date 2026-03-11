<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property array<int, string>|null $tags
 * @property PrescriptionSubType $subtipo
 * @property array<int, array<string, mixed>> $itens
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class ModeloPrescricao extends Model
{
    use HasFactory;

    protected $table = 'modelos_prescricao';

    protected $fillable = [
        'user_id',
        'nome',
        'tags',
        'subtipo',
        'itens',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'subtipo' => PrescriptionSubType::class,
            'itens' => 'array',
        ];
    }

    /**
     * @param  Builder<ModeloPrescricao>  $query
     * @return Builder<ModeloPrescricao>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\PrescriptionTemplateFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\PrescriptionTemplateFactory::new();
    }
}
