<?php

namespace App\Services;

use DOMDocument;
use InvalidArgumentException;

/**
 * Membaca file GPX (ekspor dari Strava, Garmin, Nike Run Club, dll.)
 * dan menghasilkan jarak, durasi, serta rute yang aman ditampilkan.
 */
class GpxParser
{
    /** Jumlah titik maksimum yang disimpan untuk peta. */
    public const MAX_STORED_POINTS = 1500;

    /** Meter yang disembunyikan di awal & akhir rute agar alamat rumah tidak terlihat. */
    public const PRIVACY_TRIM_M = 200;

    public function parse(string $xml, bool $privacyTrim = true): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        // LIBXML_NONET: jangan pernah mengambil resource eksternal dari file unggahan.
        $loaded = $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new InvalidArgumentException('File bukan GPX yang valid.');
        }

        $nodes = $doc->getElementsByTagNameNS('*', 'trkpt');
        if ($nodes->length === 0) {
            $nodes = $doc->getElementsByTagNameNS('*', 'rtept');
        }

        $points = [];
        $times = [];
        foreach ($nodes as $node) {
            $lat = (float) $node->getAttribute('lat');
            $lng = (float) $node->getAttribute('lon');
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
                continue;
            }
            $points[] = [$lat, $lng];

            $timeNode = $node->getElementsByTagNameNS('*', 'time')->item(0);
            if ($timeNode && ($t = strtotime(trim($timeNode->textContent))) !== false) {
                $times[] = $t;
            }
        }

        if (count($points) < 2) {
            throw new InvalidArgumentException('GPX tidak berisi titik rute yang cukup.');
        }

        $cumulative = [0.0];
        for ($i = 1, $n = count($points); $i < $n; $i++) {
            $cumulative[$i] = $cumulative[$i - 1] + $this->haversine($points[$i - 1], $points[$i]);
        }
        $total = end($cumulative);

        $nameNode = $doc->getElementsByTagNameNS('*', 'name')->item(0);

        return [
            'name' => $nameNode ? mb_substr(trim($nameNode->textContent), 0, 120) : null,
            'distance_m' => (int) round($total),
            'duration_s' => count($times) >= 2 ? max($times) - min($times) : null,
            'started_at' => $times ? date('c', min($times)) : null,
            'route' => $this->downsample($privacyTrim ? $this->trim($points, $cumulative, $total) : $points),
            'point_count' => count($points),
        ];
    }

    /** Buang titik di 200 m pertama & terakhir (rute pendek tetap disisakan bagian tengahnya). */
    protected function trim(array $points, array $cumulative, float $total): array
    {
        $trim = min(self::PRIVACY_TRIM_M, $total / 4);
        $kept = [];
        foreach ($points as $i => $pt) {
            if ($cumulative[$i] >= $trim && $cumulative[$i] <= $total - $trim) {
                $kept[] = $pt;
            }
        }

        return count($kept) >= 2 ? $kept : array_slice($points, (int) (count($points) / 3), max(2, (int) (count($points) / 3)));
    }

    protected function downsample(array $points): array
    {
        $step = max(1, (int) ceil(count($points) / self::MAX_STORED_POINTS));
        $out = [];
        foreach ($points as $i => $pt) {
            if ($i % $step === 0) {
                $out[] = [round($pt[0], 5), round($pt[1], 5)];
            }
        }

        return $out;
    }

    protected function haversine(array $a, array $b): float
    {
        $r = 6371000;
        $dLat = deg2rad($b[0] - $a[0]);
        $dLng = deg2rad($b[1] - $a[1]);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($h)));
    }
}
