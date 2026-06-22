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
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('gender', ['lelaki', 'perempuan']);
            $table->enum('race', ['melayu', 'cina', 'india']);
            $table->unsignedInteger('credits')->default(5);
            $table->string('last_free_topup_month', 7)->nullable();
            $table->boolean('age_confirmed')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
