<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RFID Scan Debounce Seconds
    |--------------------------------------------------------------------------
    |
    | Prevents accidental immediate double-taps for the same card from
    | recording duplicate loops. Configurable via environment variable.
    |
    */
    'debounce_seconds' => (int) env('RFID_DEBOUNCE_SECONDS', 3),

    /*
    | Panjang satu putaran lintasan (meter) — KONFIGURASI DEMO.
    | Jarak lomba peserta = jumlah loop x nilai ini.
    */
    'track_length_m' => (int) env('RFID_TRACK_LENGTH_M', 400),

    /*
    | Penyedia gambar peta (Leaflet). Default: OpenStreetMap, gratis tanpa API key.
    | MAP_DARK_FILTER=true membalik warna tile terang agar cocok dengan tema gelap.
    | Ganti MAP_TILE_URL bila punya layanan lain (mis. MapTiler/Stadia dengan key).
    */
    'map' => [
        'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 19),
        'dark_filter' => (bool) env('MAP_DARK_FILTER', true),
    ],

    'sample_uid' => env('SAMPLE_RFID_UID', '0007352925'),
];
