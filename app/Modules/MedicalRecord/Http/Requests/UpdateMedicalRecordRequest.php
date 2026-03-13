<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Http\Requests\Concerns\MedicalRecordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateMedicalRecordRequest extends FormRequest
{
    use MedicalRecordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $anthropometry = $this->anthropometryRules();
        $physicalExam = $this->physicalExamRules();
        $problemList = $this->problemListRules();
        $riskScores = $this->riskScoresRules();
        $conduct = $this->conductRules();

        $anthropometry['anthropometry'] = array_merge(['sometimes'], $anthropometry['anthropometry']);
        $physicalExam['physical_exam'] = array_merge(['sometimes'], $physicalExam['physical_exam']);
        $problemList['problem_list'] = array_merge(['sometimes'], $problemList['problem_list']);
        $conduct['conduct'] = array_merge(['sometimes'], $conduct['conduct']);

        $riskScores['risk_scores'] = ['sometimes', 'nullable', 'array'];

        return array_merge(
            $anthropometry,
            $physicalExam,
            $problemList,
            $riskScores,
            $conduct,
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->sharedMessages();
    }
}
