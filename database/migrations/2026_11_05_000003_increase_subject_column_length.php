<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('subject')->nullable()->change();
        });
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('subject', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subject', 100)->nullable()->change();
        });
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('subject', 100)->change();
        });
    }
};
