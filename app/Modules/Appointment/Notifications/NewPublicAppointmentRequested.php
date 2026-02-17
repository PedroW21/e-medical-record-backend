<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Notifications;

use App\Modules\Appointment\Models\Consulta;
use App\Modules\Notification\Traits\RespectsChannelPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewPublicAppointmentRequested extends Notification
{
    use Queueable;
    use RespectsChannelPreferences;

    public function __construct(
        private readonly Consulta $appointment,
    ) {}

    public static function notificationType(): string
    {
        return 'new_public_appointment_requested';
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels($notifiable);
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
            'notification_type' => self::notificationType(),
            'appointment_id' => $this->appointment->id,
            'requester_name' => $this->appointment->nome_solicitante,
            'date' => $this->appointment->data,
            'time' => $this->appointment->horario,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
