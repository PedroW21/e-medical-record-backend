<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Events;

use App\Modules\Appointment\Models\Consulta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PublicAppointmentRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Consulta $appointment,
    ) {}
}
