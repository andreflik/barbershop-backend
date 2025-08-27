<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE t1 FROM agendar_cortes t1
            INNER JOIN agendar_cortes t2
                ON t1.data_agendamento = t2.data_agendamento
               AND t1.hora_agendamento = t2.hora_agendamento
               AND t1.id > t2.id
        ');

        Schema::table('agendar_cortes', function (Blueprint $table) {
            $table->unique(
                ['data_agendamento', 'hora_agendamento'],
                'uq_agendar_data_hora'
            );
        });
    }

    public function down(): void
    {
        Schema::table('agendar_cortes', function (Blueprint $table) {
            $table->dropUnique('uq_agendar_data_hora');
        });
    }
};
