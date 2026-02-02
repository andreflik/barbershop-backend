<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_full_day')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->string('reason', 255)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamps();
            $table->index('date');
            $table->index(['is_recurring', 'weekday']);
            $table->index(['date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_periods');
    }
};
