<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\AttachmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadAttachmentRequest extends FormRequest
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
            'tipo_anexo' => ['required', Rule::enum(AttachmentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif', 'max:10240'],
            'nome' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_anexo.required' => 'O campo tipo de anexo é obrigatório.',
            'tipo_anexo.enum' => 'O tipo de anexo informado é inválido.',
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O arquivo enviado é inválido.',
            'file.mimes' => 'O arquivo deve ser PDF, JPG, JPEG, PNG ou GIF.',
            'file.max' => 'O arquivo não pode exceder 10 MB.',
            'nome.max' => 'O nome não pode exceder 255 caracteres.',
        ];
    }
}
