<?php

namespace App\Services;

use App\Models\AttendanceLocation;

class AttendanceLocationService
{
    /**
     * Jarak Haversine dalam meter antara dua titik koordinat.
     */
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    /**
     * Validasi lokasi karyawan di BACKEND terhadap lokasi kantor aktif.
     *
     * $enforce = false → mode catat-saja (dipakai absen pulang dari lapangan):
     * jarak tetap dihitung untuk arsip admin, tanpa penolakan radius/akurasi.
     *
     * state: 'unchecked' (tidak ditegakkan / belum ada lokasi aktif),
     *        'ok', 'poor_accuracy', 'outside'.
     */
    public static function check(?float $latitude, ?float $longitude, ?float $accuracy, bool $enforce = true): array
    {
        $location = AttendanceLocation::active();
        $distance = ($location && $latitude !== null && $longitude !== null)
            ? (int) round(static::distanceMeters($latitude, $longitude, $location->latitude, $location->longitude))
            : null;

        if (! $location || ! $enforce) {
            return [
                'location' => $location,
                'distance' => $distance,
                'state' => 'unchecked',
                'message' => 'Lokasi hanya dicatat.',
            ];
        }

        if ($accuracy !== null && $accuracy > $location->max_accuracy_meters) {
            return [
                'location' => $location,
                'distance' => null,
                'state' => 'poor_accuracy',
                'message' => 'Lokasi belum cukup akurat (±'.(int) $accuracy.' m, batas ±'.$location->max_accuracy_meters.' m). Aktifkan GPS dan coba kembali.',
            ];
        }

        $distance = static::distanceMeters($latitude, $longitude, $location->latitude, $location->longitude);

        if ($distance > $location->radius_meters) {
            return [
                'location' => $location,
                'distance' => (int) round($distance),
                'state' => 'outside',
                'message' => 'Anda berada di luar area absensi (±'.(int) round($distance).' m dari '.$location->name.', radius '.$location->radius_meters.' m). Silakan berada di lokasi yang telah ditentukan.',
            ];
        }

        return [
            'location' => $location,
            'distance' => (int) round($distance),
            'state' => 'ok',
            'message' => 'Lokasi sesuai (±'.(int) round($distance).' m dari '.$location->name.').',
        ];
    }
}
