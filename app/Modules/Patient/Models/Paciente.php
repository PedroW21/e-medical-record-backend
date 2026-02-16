<?php

declare(strict_types=1);

namespace App\Modules\Patient\Models;

use App\Models\User;
use App\Modules\Patient\Enums\BloodType;
use App\Modules\Patient\Enums\Gender;
use App\Modules\Patient\Enums\HabitIntensity;
use App\Modules\Patient\Enums\PatientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Patient model mapped to the "pacientes" table.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property string $cpf
 * @property string $telefone
 * @property string|null $email
 * @property \Illuminate\Support\Carbon $data_nascimento
 * @property Gender $sexo
 * @property BloodType|null $tipo_sanguineo
 * @property HabitIntensity|null $historico_tabagismo
 * @property HabitIntensity|null $historico_alcool
 * @property PatientStatus $status
 * @property \Illuminate\Support\Carbon|null $ultima_consulta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Endereco|null $endereco
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Alergia> $alergias
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CondicaoCronica> $condicoesCronicas
 */
class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'user_id',
        'nome',
        'cpf',
        'telefone',
        'email',
        'data_nascimento',
        'sexo',
        'tipo_sanguineo',
        'historico_tabagismo',
        'historico_alcool',
        'status',
        'ultima_consulta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'sexo' => Gender::class,
            'tipo_sanguineo' => BloodType::class,
            'historico_tabagismo' => HabitIntensity::class,
            'historico_alcool' => HabitIntensity::class,
            'status' => PatientStatus::class,
            'ultima_consulta' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<Endereco, $this>
     */
    public function endereco(): HasOne
    {
        return $this->hasOne(Endereco::class);
    }

    /**
     * @return BelongsToMany<Alergia, $this>
     */
    public function alergias(): BelongsToMany
    {
        return $this->belongsToMany(Alergia::class, 'alergia_paciente');
    }

    /**
     * @return BelongsToMany<CondicaoCronica, $this>
     */
    public function condicoesCronicas(): BelongsToMany
    {
        return $this->belongsToMany(CondicaoCronica::class, 'condicao_cronica_paciente');
    }

    protected static function newFactory(): \App\Modules\Patient\Database\Factories\PatientFactory
    {
        return \App\Modules\Patient\Database\Factories\PatientFactory::new();
    }
}
