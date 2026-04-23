<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Rules;

use App\Modules\MedicalRecord\Models\Anexo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

final class AttachmentLinkable implements ValidationRule
{
    /**
     * @param  class-string<Model>|null  $resultModelClass  FQCN of the exam-result Eloquent model used for uniqueness check.
     */
    public function __construct(
        private readonly int $prontuarioId,
        private readonly int $doctorUserId,
        private readonly bool $allowMultipleLinks = false,
        private readonly ?int $ignoreResultId = null,
        private readonly ?string $resultModelClass = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $anexo = Anexo::query()->with('prontuario')->find((int) $value);

        if ($anexo === null) {
            $fail('O anexo informado não foi encontrado.');

            return;
        }

        if ($anexo->prontuario_id !== $this->prontuarioId) {
            $fail('O anexo informado pertence a outro prontuário.');

            return;
        }

        if ($anexo->prontuario->user_id !== $this->doctorUserId) {
            $fail('O anexo informado não pertence ao médico autenticado.');

            return;
        }

        if ($this->allowMultipleLinks || $this->resultModelClass === null) {
            return;
        }

        /** @var class-string<Model> $class */
        $class = $this->resultModelClass;

        $query = $class::query()->where('anexo_id', $anexo->id);

        if ($this->ignoreResultId !== null) {
            $query->whereKeyNot($this->ignoreResultId);
        }

        if ($query->exists()) {
            $fail('Este anexo já está vinculado a outro resultado de exame.');
        }
    }
}
