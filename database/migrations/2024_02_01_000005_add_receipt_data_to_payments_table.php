<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->longText('receipt_data')->nullable()->after('receipt_path');
        });

        // receipt_path lama dah tak digunakan utk simpan gambar baru (gambar resit
        // sekarang disimpan terus dalam DB sebagai base64 - sama sebab dgn QR code).
        DB::statement('ALTER TABLE payments MODIFY receipt_path VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('receipt_data');
        });
    }
};
