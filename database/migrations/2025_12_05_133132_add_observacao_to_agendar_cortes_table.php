<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agendar_cortes', function (Blueprint $table) {
            $table->string('observacao')->nullable()->after('slots_bloqueados');
        });
    }

    public function down()
    {
        Schema::table('agendar_cortes', function (Blueprint $table) {
            $table->dropColumn('observacao');
        });
    }
};
