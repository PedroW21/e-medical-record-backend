<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;
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

        return collect($storeRules)
            ->map(fn (array $rules): array => collect($rules)
                ->map(fn (string $rule): string => $rule === 'required' ? 'sometimes' : $rule)
                ->values()
                ->all())
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->examMessages();
    }
}
