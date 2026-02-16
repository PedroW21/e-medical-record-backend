<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BookPublicAppointmentRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'data' => ['required', 'date', 'after_or_equal:today'],
            'horario' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'telefone.required' => 'O campo telefone é obrigatório.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'O campo e-mail deve ser um endereço de e-mail válido.',
            'observacoes.max' => 'O campo observações não pode ter mais de 1000 caracteres.',
            'data.required' => 'O campo data é obrigatório.',
            'data.date' => 'O campo data deve ser uma data válida.',
            'data.after_or_equal' => 'A data deve ser igual ou posterior a hoje.',
            'horario.required' => 'O campo horário é obrigatório.',
            'horario.regex' => 'O campo horário deve estar no formato HH:MM.',
        ];
    }
}
