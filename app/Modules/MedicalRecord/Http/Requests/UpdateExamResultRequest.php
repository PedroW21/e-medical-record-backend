<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Rules\AttachmentLinkable;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateExamResultRequest extends FormRequest
{
    use ExamResultValidationRules;

    /**
     * Returns validation rules with all 'required' constraints replaced by 'sometimes'
     * to allow partial updates.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $examType = ExamType::from($this->route('examType'));
        $storeRules = $this->storeRulesFor($examType);

        $rules = collect($storeRules)
            ->map(fn (array $rules): array => collect($rules)
                ->map(fn (string $rule): string => $rule === 'required' ? 'sometimes' : $rule)
                ->values()
                ->all())
            ->all();

        $rules['anexo_id'] = [
            'nullable',
            'integer',
            new AttachmentLinkable(
                prontuarioId: (int) $this->route('medicalRecordId'),
                doctorUserId: (int) $this->user()->id,
                ignoreResultId: $this->resolveIgnoreResultId(),
                resultModelClass: $this->resolveExamTypeModelClass(),
            ),
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            $this->examMessages(),
            [
                'anexo_id.integer' => 'O identificador do anexo deve ser um número inteiro.',
            ],
        );
    }

    /**
     * Resolves the Eloquent model class name for the current exam type.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    private function resolveExamTypeModelClass(): string
    {
        return ExamType::from((string) $this->route('examType'))->modelClass();
    }

    /**
     * Returns the result ID that should be ignored for the uniqueness check.
     * Update requests ignore the current result so re-saving its own attachment is allowed.
     */
    private function resolveIgnoreResultId(): ?int
    {
        return (int) $this->route('id');
    }
}
