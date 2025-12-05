<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agendar_cortes', function (Blueprint $table) {
            $table->unsignedTinyInteger('slots_bloqueados')
                ->default(1)
                ->after('hora_agendamento');
        });
    }

    public function down(): void
    {
        Schema::table('agendar_cortes', function (Blueprint $table) {
            $table->dropColumn('slots_bloqueados');
        });
    }
};
