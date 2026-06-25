<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Settings.value perlu boleh simpan base64 QR code (gambar) terus dalam DB.
    // Ini elak isu gambar QR "hilang"/tak keluar bila storage fail/local disk
    // di-reset oleh platform hosting (Railway) selepas redeploy.
    public function up(): void
    {
        DB::statement('ALTER TABLE settings MODIFY value LONGTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE settings MODIFY value TEXT NULL');
    }
};
