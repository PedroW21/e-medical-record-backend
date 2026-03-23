<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;

final class StoreExamResultRequest extends FormRequest
{
    use ExamResultValidationRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $examType = ExamType::from($this->route('examType'));

        return $this->storeRulesFor($examType);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->examMessages();
    }
}
