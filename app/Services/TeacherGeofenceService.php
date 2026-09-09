<?php

namespace App\Services;

use App\Models\SchoolAttendanceSetting;

class TeacherGeofenceService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function verify(
        SchoolAttendanceSetting $settings,
        ?float $latitude,
        ?float $longitude,
        ?float $accuracy
    ): array {
        if (! $settings->teacher_geofence_enabled) {
            return [
                'valid' => true,
                'enabled' => false,
                'message' => null,
                'latitude' => null,
                'longitude' => null,
                'accuracy' => null,
                'distance' => null,
                'radius' => null,
            ];
        }

        $centerLatitude = $this->nullableFloat($settings->teacher_geofence_latitude);
        $centerLongitude = $this->nullableFloat($settings->teacher_geofence_longitude);
        $radius = max(20, (float) ($settings->teacher_geofence_radius_meters ?: 200));
        $maxAccuracy = max(10, (float) ($settings->teacher_geofence_max_accuracy_meters ?: 100));

        if ($centerLatitude === null || $centerLongitude === null) {
            return $this->rejected(
                'Zona absensi guru belum lengkap. Hubungi admin untuk mengatur titik sekolah.',
                $radius
            );
        }

        if ($latitude === null || $longitude === null || $accuracy === null) {
            return $this->rejected(
                'Lokasi wajib diaktifkan untuk melakukan absensi guru.',
                $radius
            );
        }

        if ($accuracy > $maxAccuracy) {
            return $this->rejected(
                'Akurasi lokasi masih '.number_format($accuracy, 0, ',', '.').' meter. '
                .'Tunggu GPS lebih akurat (maksimal '.number_format($maxAccuracy, 0, ',', '.').' meter).',
                $radius,
                $latitude,
                $longitude,
                $accuracy
            );
        }

        $distance = $this->distanceMeters(
            $centerLatitude,
            $centerLongitude,
            $latitude,
            $longitude
        );

        if ($distance > $radius) {
            return $this->rejected(
                'Lokasi berada sekitar '.number_format($distance, 0, ',', '.').' meter dari titik sekolah. '
                .'Batas absensi adalah '.number_format($radius, 0, ',', '.').' meter.',
                $radius,
                $latitude,
                $longitude,
                $accuracy,
                $distance
            );
        }

        return [
            'valid' => true,
            'enabled' => true,
            'message' => null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => round($accuracy, 2),
            'distance' => round($distance, 2),
            'radius' => round($radius, 2),
        ];
    }

    public function distanceMeters(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $fromLatitudeRadians = deg2rad($fromLatitude);
        $toLatitudeRadians = deg2rad($toLatitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitudeRadians)
            * cos($toLatitudeRadians)
            * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * asin(min(1, sqrt($a)));
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function rejected(
        string $message,
        float $radius,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracy = null,
        ?float $distance = null
    ): array {
        return [
            'valid' => false,
            'enabled' => true,
            'message' => $message,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'distance' => $distance,
            'radius' => $radius,
        ];
    }
}
