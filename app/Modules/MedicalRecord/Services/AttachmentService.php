<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\ConfirmAttachmentDTO;
use App\Modules\MedicalRecord\DTOs\UploadAttachmentDTO;
use App\Modules\MedicalRecord\Enums\FileType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Events\AttachmentConfirmed;
use App\Modules\MedicalRecord\Events\AttachmentUploaded;
use App\Modules\MedicalRecord\Jobs\ParseAttachmentJob;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AttachmentService
{
    /**
     * @return Collection<int, Anexo>
     */
    public function listForProntuario(int $prontuarioId): Collection
    {
        return Anexo::query()
            ->where('prontuario_id', $prontuarioId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findOrFail(int $id): Anexo
    {
        $attachment = Anexo::query()->with('prontuario')->find($id);

        if (! $attachment) {
            throw new NotFoundHttpException('Anexo não encontrado.');
        }

        return $attachment;
    }

    public function findMedicalRecordOrFail(int $prontuarioId): Prontuario
    {
        $prontuario = Prontuario::query()->find($prontuarioId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    public function upload(UploadAttachmentDTO $dto): Anexo
    {
        $prontuario = $this->findMedicalRecordOrFail($dto->prontuarioId);
        $this->ensureDraft($prontuario);

        $extension = strtolower($dto->file->getClientOriginalExtension());
        $tipoArquivo = FileType::fromExtension($extension);

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = 'anexos/'.$prontuario->id.'/'.$filename;

        Storage::disk('anexos')->put($path, file_get_contents($dto->file->getRealPath()));

        $initialStatus = $dto->tipoAnexo->isParseable() ? ProcessingStatus::Pending : null;

        $attachment = Anexo::query()->create([
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'tipo_anexo' => $dto->tipoAnexo,
            'nome' => $dto->nome ?? $dto->file->getClientOriginalName(),
            'tipo_arquivo' => $tipoArquivo,
            'caminho' => $path,
            'tamanho_bytes' => $dto->file->getSize(),
            'status_processamento' => $initialStatus,
        ]);

        AttachmentUploaded::dispatch($attachment);

        if ($attachment->isParseable()) {
            ParseAttachmentJob::dispatch($attachment->id);
        }

        return $attachment->fresh();
    }

    public function retryParse(int $id): Anexo
    {
        $attachment = $this->findOrFail($id);

        if (! $attachment->isParseable()) {
            throw new ConflictHttpException('Este tipo de anexo não é processável por IA.');
        }

        $attachment->update([
            'status_processamento' => ProcessingStatus::Pending,
            'erro_processamento' => null,
        ]);

        ParseAttachmentJob::dispatch($attachment->id);

        return $attachment->fresh();
    }

    public function confirm(ConfirmAttachmentDTO $dto): Anexo
    {
        return DB::transaction(function () use ($dto): Anexo {
            $attachment = $this->findOrFail($dto->attachmentId);

            if (! $attachment->isParseable()) {
                throw new ConflictHttpException('Este tipo de anexo não requer confirmação.');
            }

            if ($attachment->status_processamento?->canBeConfirmed() !== true) {
                throw new ConflictHttpException('Somente anexos com processamento concluído podem ser confirmados.');
            }

            $attachment->update([
                'dados_extraidos' => $dto->examData,
                'status_processamento' => ProcessingStatus::Confirmed,
                'confirmado_em' => now(),
            ]);

            $fresh = $attachment->fresh();
            AttachmentConfirmed::dispatch($fresh);

            return $fresh;
        });
    }

    public function delete(int $id): void
    {
        $attachment = $this->findOrFail($id);

        if ($attachment->status_processamento?->canBeDeleted() === false) {
            throw new ConflictHttpException('Não é possível remover um anexo já confirmado.');
        }

        Storage::disk('anexos')->delete($attachment->caminho);
        $attachment->delete();
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível anexar arquivos a um prontuário finalizado.');
        }
    }
}
