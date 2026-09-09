<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('school_attendance_settings', function (Blueprint $table) {
                $table->boolean('teacher_geofence_enabled')->default(false)->after('is_active');
                $table->decimal('teacher_geofence_latitude', 10, 7)->nullable()->after('teacher_geofence_enabled');
                $table->decimal('teacher_geofence_longitude', 10, 7)->nullable()->after('teacher_geofence_latitude');
                $table->unsignedInteger('teacher_geofence_radius_meters')->default(200)->after('teacher_geofence_longitude');
                $table->unsignedInteger('teacher_geofence_max_accuracy_meters')->default(100)->after('teacher_geofence_radius_meters');
            });

            Schema::table('school_teacher_attendances', function (Blueprint $table) {
                $table->decimal('check_in_latitude', 10, 7)->nullable()->after('check_in_status');
                $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
                $table->decimal('check_in_accuracy_meters', 8, 2)->nullable()->after('check_in_longitude');
                $table->decimal('check_in_distance_meters', 10, 2)->nullable()->after('check_in_accuracy_meters');
                $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_out_status');
                $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
                $table->decimal('check_out_accuracy_meters', 8, 2)->nullable()->after('check_out_longitude');
                $table->decimal('check_out_distance_meters', 10, 2)->nullable()->after('check_out_accuracy_meters');
            });
        } catch (Throwable $exception) {
            $this->removeColumns();

            throw $exception;
        }
    }

    public function down(): void
    {
        $this->removeColumns();
    }

    private function removeColumns(): void
    {
        $this->dropExistingColumns('school_teacher_attendances', [
            'check_in_latitude',
            'check_in_longitude',
            'check_in_accuracy_meters',
            'check_in_distance_meters',
            'check_out_latitude',
            'check_out_longitude',
            'check_out_accuracy_meters',
            'check_out_distance_meters',
        ]);

        $this->dropExistingColumns('school_attendance_settings', [
            'teacher_geofence_enabled',
            'teacher_geofence_latitude',
            'teacher_geofence_longitude',
            'teacher_geofence_radius_meters',
            'teacher_geofence_max_accuracy_meters',
        ]);
    }

    private function dropExistingColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($tableName, $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
