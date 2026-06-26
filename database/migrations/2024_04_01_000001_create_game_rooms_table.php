<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('game'); // 'chess' atau 'uno'
            $table->enum('status', ['waiting', 'active', 'finished'])->default('waiting');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('min_players')->default(2);
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->json('state')->nullable(); // papan/kad/giliran dll - state penuh permainan
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
