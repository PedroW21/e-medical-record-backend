<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('medical-records.{prontuarioId}', function (User $user, int $prontuarioId): bool {
    return Prontuario::query()
        ->whereKey($prontuarioId)
        ->where('user_id', $user->id)
        ->exists();
});
