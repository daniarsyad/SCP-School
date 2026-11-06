<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable();
            $table->string('address', 500)->nullable();
        });

        Schema::create('guru_bk_classes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->primary(['user_id', 'class_id']);
        });

        DB::table('guru_bk_classes')->insertUsing(
            ['user_id', 'class_id'],
            DB::table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.role_name', 'Guru BK')
                ->whereNotNull('users.class_id')
                ->select('users.id', 'users.class_id')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_bk_classes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address']);
        });
    }
};
