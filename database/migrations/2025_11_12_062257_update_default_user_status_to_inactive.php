<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing users: set inactive if not verified, active if verified
        DB::statement("
            UPDATE users
            SET statut = CASE
                WHEN is_verified = 1 THEN 'actif'
                ELSE 'inactif'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all users to active (reverse operation)
        DB::statement("UPDATE users SET statut = 'actif'");
    }
};
