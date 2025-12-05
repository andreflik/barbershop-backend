<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agendar_corte_servico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')
                ->constrained('agendar_cortes')
                ->onDelete('cascade');

            $table->foreignId('servico_id')
                ->constrained('servicos')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['agendamento_id', 'servico_id'], 'uq_agendamento_servico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendar_corte_servico');
    }
};
