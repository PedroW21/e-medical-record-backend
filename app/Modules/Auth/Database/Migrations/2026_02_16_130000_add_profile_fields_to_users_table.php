<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default(UserRole::Doctor->value)->after('password');
            $table->string('crm')->nullable()->after('role');
            $table->string('specialty')->nullable()->after('crm');
            $table->string('avatar_url')->nullable()->after('specialty');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'crm', 'specialty', 'avatar_url']);
        });
    }
};
