<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Database\Factories\AttachmentFactory;
use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\FileType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property int $paciente_id
 * @property AttachmentType $tipo_anexo
 * @property string $nome
 * @property FileType $tipo_arquivo
 * @property string $caminho
 * @property int $tamanho_bytes
 * @property ProcessingStatus|null $status_processamento
 * @property array<string, mixed>|null $dados_extraidos
 * @property string|null $erro_processamento
 * @property \Illuminate\Support\Carbon|null $processado_em
 * @property \Illuminate\Support\Carbon|null $confirmado_em
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class Anexo extends Model
{
    use HasFactory;

    protected $table = 'anexos';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'tipo_anexo',
        'nome',
        'tipo_arquivo',
        'caminho',
        'tamanho_bytes',
        'status_processamento',
        'dados_extraidos',
        'erro_processamento',
        'processado_em',
        'confirmado_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_anexo' => AttachmentType::class,
            'tipo_arquivo' => FileType::class,
            'status_processamento' => ProcessingStatus::class,
            'dados_extraidos' => 'array',
            'tamanho_bytes' => 'integer',
            'processado_em' => 'datetime',
            'confirmado_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class, 'prontuario_id');
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function isParseable(): bool
    {
        return $this->tipo_anexo->isParseable();
    }

    protected static function newFactory(): Factory
    {
        return AttachmentFactory::new();
    }
}
