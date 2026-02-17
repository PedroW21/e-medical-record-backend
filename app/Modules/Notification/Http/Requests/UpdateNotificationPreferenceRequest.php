<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationPreferenceRequest extends FormRequest
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
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.type' => ['required', 'string', 'max:255'],
            'preferences.*.channel' => ['required', 'string', 'in:mail,broadcast,sms,whatsapp'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'O campo preferências é obrigatório.',
            'preferences.array' => 'O campo preferências deve ser um array.',
            'preferences.min' => 'É necessário informar ao menos uma preferência.',
            'preferences.*.type.required' => 'O tipo de notificação é obrigatório.',
            'preferences.*.channel.required' => 'O canal é obrigatório.',
            'preferences.*.channel.in' => 'O canal informado é inválido.',
            'preferences.*.enabled.required' => 'O campo habilitado é obrigatório.',
            'preferences.*.enabled.boolean' => 'O campo habilitado deve ser verdadeiro ou falso.',
        ];
    }
}
