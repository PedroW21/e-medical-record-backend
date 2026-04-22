<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\FileType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anexo>
 */
final class AttachmentFactory extends Factory
{
    protected $model = Anexo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prontuario = Prontuario::factory()->create();
        $tipoAnexo = fake()->randomElement(AttachmentType::cases());
        $tipoArquivo = fake()->randomElement(FileType::cases());

        return [
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'tipo_anexo' => $tipoAnexo,
            'nome' => fake()->words(2, true).'.'.$tipoArquivo->value,
            'tipo_arquivo' => $tipoArquivo,
            'caminho' => 'anexos/'.$prontuario->id.'/'.fake()->uuid().'.'.$tipoArquivo->value,
            'tamanho_bytes' => fake()->numberBetween(1024, 5_000_000),
            'status_processamento' => $tipoAnexo->isParseable() ? ProcessingStatus::Pending : null,
        ];
    }

    public function parseable(): static
    {
        return $this->state(fn () => [
            'tipo_anexo' => AttachmentType::Ecg,
            'status_processamento' => ProcessingStatus::Pending,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status_processamento' => ProcessingStatus::Completed,
            'dados_extraidos' => ['date' => now()->toDateString(), 'pattern' => 'normal'],
            'processado_em' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status_processamento' => ProcessingStatus::Failed,
            'erro_processamento' => 'Stub failure for test',
            'processado_em' => now(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->completed()->state(fn () => [
            'status_processamento' => ProcessingStatus::Confirmed,
            'confirmado_em' => now(),
        ]);
    }
}
