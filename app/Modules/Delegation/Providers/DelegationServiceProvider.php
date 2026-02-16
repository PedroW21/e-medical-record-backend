<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Providers;

use App\Modules\Delegation\Models\Delegacao;
use App\Modules\Delegation\Policies\DelegationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class DelegationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Delegacao::class, DelegationPolicy::class);
    }
}
