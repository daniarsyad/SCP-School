<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nis', 30)->nullable()->after('class_id');
            $table->unique(['role_id', 'nis']);
            $table->string('nip', 30)->nullable()->unique()->after('nis');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropUnique(['role_id', 'nis']);
            $table->dropColumn(['nis', 'nip']);
        });
    }
};
