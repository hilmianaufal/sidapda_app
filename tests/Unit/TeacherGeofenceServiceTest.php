<?php

namespace Tests\Unit;

use App\Models\SchoolAttendanceSetting;
use App\Services\TeacherGeofenceService;
use PHPUnit\Framework\TestCase;

class TeacherGeofenceServiceTest extends TestCase
{
    public function test_disabled_zone_allows_scan_without_location(): void
    {
        $settings = new SchoolAttendanceSetting([
            'teacher_geofence_enabled' => false,
        ]);

        $result = (new TeacherGeofenceService())->verify($settings, null, null, null);

        self::assertTrue($result['valid']);
        self::assertFalse($result['enabled']);
    }

    public function test_location_inside_radius_is_accepted(): void
    {
        $settings = $this->settings();

        $result = (new TeacherGeofenceService())->verify(
            $settings,
            -6.7061000,
            108.5571000,
            12.5
        );

        self::assertTrue($result['valid']);
        self::assertLessThanOrEqual(200, $result['distance']);
    }

    public function test_location_outside_radius_is_rejected(): void
    {
        $settings = $this->settings();

        $result = (new TeacherGeofenceService())->verify(
            $settings,
            -6.7090000,
            108.5571000,
            10
        );

        self::assertFalse($result['valid']);
        self::assertGreaterThan(200, $result['distance']);
    }

    public function test_inaccurate_location_is_rejected(): void
    {
        $settings = $this->settings();

        $result = (new TeacherGeofenceService())->verify(
            $settings,
            -6.7061000,
            108.5571000,
            150
        );

        self::assertFalse($result['valid']);
        self::assertStringContainsString('Akurasi lokasi', $result['message']);
    }

    private function settings(): SchoolAttendanceSetting
    {
        return new SchoolAttendanceSetting([
            'teacher_geofence_enabled' => true,
            'teacher_geofence_latitude' => -6.7061000,
            'teacher_geofence_longitude' => 108.5571000,
            'teacher_geofence_radius_meters' => 200,
            'teacher_geofence_max_accuracy_meters' => 50,
        ]);
    }
}
