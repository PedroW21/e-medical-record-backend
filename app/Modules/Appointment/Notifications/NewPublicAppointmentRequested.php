<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Notifications;

use App\Modules\Appointment\Models\Consulta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewPublicAppointmentRequested extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Consulta $appointment,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nova solicitação de consulta')
            ->greeting('Olá!')
            ->line("Uma nova solicitação de consulta foi recebida de {$this->appointment->nome_solicitante}.")
            ->line("Data: {$this->appointment->data}")
            ->line("Horário: {$this->appointment->horario}")
            ->line("Telefone: {$this->appointment->telefone_solicitante}")
            ->line("E-mail: {$this->appointment->email_solicitante}")
            ->action('Ver consultas', url('/appointments'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'requester_name' => $this->appointment->nome_solicitante,
            'date' => $this->appointment->data,
            'time' => $this->appointment->horario,
        ];
    }
}
