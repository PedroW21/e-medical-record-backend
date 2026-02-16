<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDelegationRequest extends FormRequest
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
            'secretary_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'secretary'),
                Rule::unique('delegacoes', 'secretaria_id')->where('medico_id', $this->user()?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'secretary_id.required' => 'O campo secretária é obrigatório.',
            'secretary_id.exists' => 'A secretária informada não existe ou não possui o perfil de secretária.',
            'secretary_id.unique' => 'Esta secretária já está vinculada a você.',
        ];
    }
}
