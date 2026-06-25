<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_sessions', function (Blueprint $table) {
            $table->foreignId('blocked_by')->nullable()->after('user_b_loved')->constrained('users')->nullOnDelete();
            $table->string('origin', 20)->default('random')->after('blocked_by');
        });

        // expires_at perlu nullable sebab sesi yang bermula dari permintaan kawan
        // (friend request) terus jadi "revealed" tanpa had masa - tak perlu expiry.
        DB::statement('ALTER TABLE match_sessions MODIFY expires_at TIMESTAMP NULL');
    }

    public function down(): void
    {
        Schema::table('match_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blocked_by');
            $table->dropColumn('origin');
        });
    }
};
