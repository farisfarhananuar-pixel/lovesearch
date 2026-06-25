<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after('race');
            $table->unsignedTinyInteger('semester')->nullable()->after('age');
            $table->unsignedTinyInteger('pref_min_age')->nullable()->after('semester');
            $table->unsignedTinyInteger('pref_max_age')->nullable()->after('pref_min_age');
            $table->unsignedTinyInteger('pref_min_semester')->nullable()->after('pref_max_age');
            $table->unsignedTinyInteger('pref_max_semester')->nullable()->after('pref_min_semester');
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropColumn([
                'age', 'semester', 'pref_min_age', 'pref_max_age',
                'pref_min_semester', 'pref_max_semester',
            ]);
        });
    }
};
