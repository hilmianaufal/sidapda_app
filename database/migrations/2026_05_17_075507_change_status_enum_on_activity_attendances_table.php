<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activity_attendances MODIFY status ENUM('hadir','terlambat','izin','sakit','pulang') DEFAULT 'hadir'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activity_attendances MODIFY status ENUM('hadir','terlambat') DEFAULT 'hadir'");
    }
};
