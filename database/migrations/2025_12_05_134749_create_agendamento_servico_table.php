<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agendamento_servico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendar_corte_id');
            $table->unsignedBigInteger('servico_id');

            $table->foreign('agendar_corte_id')
                ->references('id')->on('agendar_cortes')
                ->onDelete('cascade');

            $table->foreign('servico_id')
                ->references('id')->on('servicos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento_servico');
    }
};
