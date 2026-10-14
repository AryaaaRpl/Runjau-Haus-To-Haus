<?php

namespace App\Http\Controllers;

use App\Models\RaceRoute;
use App\Services\GpxParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RaceRouteController extends Controller
{
    public function store(Request $request, GpxParser $parser): RedirectResponse
    {
        $request->validate([
            'route_gpx' => ['required', 'file', 'max:5120', 'extensions:gpx'],
            'route_name' => ['nullable', 'string', 'max:120'],
        ], [
            'route_gpx.required' => 'Pilih file GPX rute lomba.',
            'route_gpx.extensions' => 'File rute harus berformat .gpx.',
        ]);

        try {
            // Rute lomba bersifat publik: tidak perlu dipangkas seperti lari pribadi.
            $data = $parser->parse($request->file('route_gpx')->get(), privacyTrim: false);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['route_gpx' => $e->getMessage()]);
        }

        RaceRoute::query()->delete(); // hanya satu rute aktif
        RaceRoute::create([
            'name' => $request->input('route_name') ?: $data['name'],
            'distance_m' => $data['distance_m'],
            'route' => $data['route'],
        ]);

        return back()->with('success', 'Rute lomba disimpan: '.number_format($data['distance_m'] / 1000, 2, ',', '.').' km.');
    }

    public function destroy(): RedirectResponse
    {
        RaceRoute::query()->delete();

        return back()->with('success', 'Rute lomba dihapus.');
    }
}
