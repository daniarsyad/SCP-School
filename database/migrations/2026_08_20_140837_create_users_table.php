<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable(); // relasi orang tua → siswa
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('fullname', 100);
            $table->timestamp('created_at')->useCurrent();
            
            // self-referencing untuk parent_id
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};