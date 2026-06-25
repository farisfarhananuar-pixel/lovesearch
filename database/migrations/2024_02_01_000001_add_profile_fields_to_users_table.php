<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('full_name');
            $table->longText('profile_photo')->nullable()->after('display_name');
            $table->unsignedTinyInteger('age')->nullable()->after('race');
            $table->unsignedTinyInteger('semester')->nullable()->after('age');
            $table->string('friend_code', 10)->nullable()->unique()->after('id');
        });

        // Backfill friend_code untuk user sedia ada (kalau ada).
        DB::table('users')->whereNull('friend_code')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'friend_code' => $this->generateUniqueCode(),
            ]);
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(2)).rand(1000, 9999);
            $exists = DB::table('users')->where('friend_code', $code)->exists();
        } while ($exists);

        return $code;
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'profile_photo', 'age', 'semester', 'friend_code']);
        });
    }
};
