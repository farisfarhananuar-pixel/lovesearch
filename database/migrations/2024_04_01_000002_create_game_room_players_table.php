<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained('game_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->boolean('is_bot')->default(false);
            $table->unsignedTinyInteger('seat')->nullable(); // diisi bila join/start, ikut turutan giliran
            $table->enum('status', ['invited', 'joined', 'declined', 'left'])->default('invited');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['game_room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_room_players');
    }
};
