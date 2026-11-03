<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('periods', 'day_group')) {
            Schema::table('periods', function (Blueprint $table) {
                $table->string('day_group', 20)->nullable()->after('is_break');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('periods', 'day_group')) {
            Schema::table('periods', function (Blueprint $table) {
                $table->dropColumn('day_group');
            });
        }
    }
};
