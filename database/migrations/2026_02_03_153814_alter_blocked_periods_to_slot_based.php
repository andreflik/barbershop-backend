<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blocked_periods', function (Blueprint $table) {

            // Só cria a coluna time se ainda não existir
            if (!Schema::hasColumn('blocked_periods', 'time')) {
                $table->time('time')->nullable()->after('date');
            }

            // Remove colunas antigas apenas se existirem
            if (Schema::hasColumn('blocked_periods', 'start_time')) {
                $table->dropColumn('start_time');
            }

            if (Schema::hasColumn('blocked_periods', 'end_time')) {
                $table->dropColumn('end_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocked_periods', function (Blueprint $table) {

            // Recria start_time se não existir
            if (!Schema::hasColumn('blocked_periods', 'start_time')) {
                $table->time('start_time')->nullable();
            }

            // Recria end_time se não existir
            if (!Schema::hasColumn('blocked_periods', 'end_time')) {
                $table->time('end_time')->nullable();
            }

            // Remove time apenas se existir
            if (Schema::hasColumn('blocked_periods', 'time')) {
                $table->dropColumn('time');
            }
        });
    }
};
