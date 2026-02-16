<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Actions;

use App\Modules\Paciente\Models\Paciente;

final class DeletePacienteAction
{
    public function execute(Paciente $paciente): void
    {
        $paciente->delete();
    }
}
