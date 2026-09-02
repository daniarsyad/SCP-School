<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_histories', function (Blueprint $table) {
            $table->id();
            $table->enum('cycle_type', ['1', '2']);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_journals')->default(0);
            $table->unsignedInteger('total_attendances')->default(0);
            $table->json('teachers')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_histories');
    }
};