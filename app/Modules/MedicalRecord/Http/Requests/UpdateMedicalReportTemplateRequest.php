<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMedicalReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'body_template' => ['sometimes', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'body_template.max' => 'O corpo do template não pode ter mais de 10000 caracteres.',
        ];
    }
}
