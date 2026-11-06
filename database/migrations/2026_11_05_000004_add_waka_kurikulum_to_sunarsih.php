<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Models\Role;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cari user dengan username SUNARSIH (case‑insensitive)
        $user = User::whereRaw('LOWER(username) = ?', ['sunarsih'])->first();
        $role = Role::where('role_name', 'Waka Kurikulum')->first();
        if ($user && $role) {
            // Attach role without removing existing roles
            $user->additionalRoles()->syncWithoutDetaching([$role->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $user = User::whereRaw('LOWER(username) = ?', ['sunarsih'])->first();
        $role = Role::where('role_name', 'Waka Kurikulum')->first();
        if ($user && $role) {
            $user->additionalRoles()->detach($role->id);
        }
    }
};
?>
