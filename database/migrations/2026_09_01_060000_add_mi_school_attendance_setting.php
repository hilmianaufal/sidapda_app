<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $institutionId = DB::table('institutions')
            ->where('code', 'mi')
            ->value('id');

        if (!$institutionId) {
            throw new RuntimeException('Lembaga MI tidak ditemukan.');
        }

        DB::table('school_attendance_settings')->insert([
            'institution_id' => $institutionId,
            'check_in_open' => '05:00:00',
            'check_in_time' => '07:20:00',
            'check_in_deadline' => '09:00:00',
            'check_out_open' => '09:01:00',
            'check_out_time' => '13:10:00',
            'check_out_deadline' => '14:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $institutionId = DB::table('institutions')
            ->where('code', 'mi')
            ->value('id');

        if ($institutionId) {
            DB::table('school_attendance_settings')
                ->where('institution_id', $institutionId)
                ->delete();
        }
    }
};
