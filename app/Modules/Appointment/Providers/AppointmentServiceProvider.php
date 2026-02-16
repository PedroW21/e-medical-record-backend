<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Providers;

use App\Modules\Appointment\Events\PublicAppointmentRequested;
use App\Modules\Appointment\Listeners\SendNewAppointmentNotification;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Policies\AppointmentPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Consulta::class, AppointmentPolicy::class);

        Event::listen(
            PublicAppointmentRequested::class,
            SendNewAppointmentNotification::class,
        );
    }
}
