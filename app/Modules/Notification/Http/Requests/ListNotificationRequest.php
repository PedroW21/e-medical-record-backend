<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListNotificationRequest extends FormRequest
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
            'status' => ['nullable', 'string', 'in:read,unread,all'],
            'type' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'O status deve ser: read, unread ou all.',
            'to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'per_page.min' => 'A quantidade por página deve ser no mínimo 1.',
            'per_page.max' => 'A quantidade por página deve ser no máximo 100.',
        ];
    }
}
