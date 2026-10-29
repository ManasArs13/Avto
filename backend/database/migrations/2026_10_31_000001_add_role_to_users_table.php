<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Новые сотрудники по умолчанию получают минимальные права.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('viewer')->after('email');
        });

        // До появления ролей все пользователи админки были администраторами — сохраняем им доступ.
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
