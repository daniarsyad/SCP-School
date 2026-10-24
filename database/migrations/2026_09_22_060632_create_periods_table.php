<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('period_number')->nullable(); // null = istirahat
            $table->string('label', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false);
            $table->timestamps();
        });

        // Relasi opsional ke schedules
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('period_id')
                ->nullable()
                ->after('end_time')
                ->constrained('periods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('period_id');
        });

        Schema::dropIfExists('periods');
    }
};