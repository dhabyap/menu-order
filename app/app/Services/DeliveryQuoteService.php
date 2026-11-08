<?php

namespace App\Services;

use App\Services\EventLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeliveryQuoteService
{
    private const NOMINATIM_MIN_INTERVAL_S = 1;

    private const OPENCAGE_MIN_INTERVAL_S = 0.2;

    private const MAPBOX_MIN_INTERVAL_S = 0.1;

    private const ROUTING_ROAD_FACTOR = 1.3;

    /**
     * Calculate a delivery quote for a customer address.
     *
     * The `fallback` flag is informational only; the server decides when
     * a base-fee estimate is appropriate. All provider failures fall
     * through to base fee so checkout is never blocked.
     *
     * @return array<string, mixed>
     */
    public function quote(string $address, bool $fallback = false): array
    {
        $settings = DB::table('store_settings')->pluck('value', 'key')->all();
        $originLat = $this->coordinate($settings['store_latitude'] ?? null, -90, 90);
        $originLon = $this->coordinate($settings['store_longitude'] ?? null, -180, 180);
        if ($originLat === null || $originLon === null) {
            throw new RuntimeException('Lokasi toko belum dikonfigurasi. Hubungi pengelola toko.');
        }

        $baseFee = max(0, (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)));

        if ($fallback) {
            // Server should decide when to use base fee, not client.
            // Attempt geocoding first. Only use base fee if geocoding fails.
            $result = $this->geocodeChain($address);
            if ($result !== null) {
                $routeMeters = $this->routeWithFallback($originLat, $originLon, $result['lat'], $result['lon']);
                if ($routeMeters === null) {
                    throw new RuntimeException('Rute tidak dapat dihitung.');
                }
                $source = $result['provider'].'+'.($routeMeters['via_haversine'] ? 'haversine' : 'osrm');
                $isEstimate = $routeMeters['via_haversine'];
                $isBaseFee = false;
                if ($routeMeters['via_haversine']) {
                    $warning = 'Jarak dihitung sebagai estimasi karena rute jalan tidak tersedia. Ongkir final dikonfirmasi kurir.';
                }
            } else {
                // Geocoding failed completely - use base fee
                $isEstimate = true;
                $isBaseFee = true;
                $warning = 'Alamat belum terverifikasi. Ongkir Rp'.number_format($baseFee, 0, ',', '.').' adalah estimasi awal. Kurir akan mengonfirmasi biaya final sebelum pengantaran.';
            }
        } else {
            // Normal flow - attempt geocoding
            $destination = null;
            $routeMeters = null;
            $source = 'base_fee';
            $isEstimate = true;
            $isBaseFee = true;
            $warning = null;

            try {
                $destination = $this->geocodeChain($address);
                if ($destination === null) {
                    throw new RuntimeException('Alamat tidak ditemukan.');
                }
                $routeMeters = $this->routeWithFallback($originLat, $originLon, $destination['lat'], $destination['lon']);
                if ($routeMeters === null) {
                    throw new RuntimeException('Rute tidak dapat dihitung.');
                }
                $source = $destination['provider'].'+'.($routeMeters['via_haversine'] ? 'haversine' : 'osrm');
                $isEstimate = $routeMeters['via_haversine'];
                $isBaseFee = false;
                if ($routeMeters['via_haversine']) {
                    $warning = 'Jarak dihitung sebagai estimasi karena rute jalan tidak tersedia. Ongkir final dikonfirmasi kurir.';
                }
            } catch (RuntimeException) {
                $isEstimate = true;
                $isBaseFee = true;
                $source = 'base_fee';
                $warning = 'Alamat belum terverifikasi. Ongkir Rp'.number_format($baseFee, 0, ',', '.').' adalah estimasi awal. Kurir akan mengonfirmasi biaya final sebelum pengantaran.';
            }
        }

        if ($isBaseFee) {
            $label = $destination['label'] ?? $address;
            return $this->buildBaseFeeQuote($address, $baseFee, $label, $warning);
        }

        $response = $this->priceQuote(
            $address,
            (int) ceil($routeMeters['distance']),
            $settings,
            $destination['label'],
            $source,
            $destination['lat'],
            $destination['lon'],
            $isEstimate,
        );

        if ($warning !== null) {
            $response['warning'] = $warning;
        }
        if (! empty($destination['suggestions']) && count($destination['suggestions']) > 1) {
            $response['suggestions'] = $destination['suggestions'];
        }

        return $response;
    }

    /**
     * Quote for a customer-selected map coordinate.
     *
     * @return array<string, mixed>
     */
    public function quoteForCoordinates(string $address, float $destinationLat, float $destinationLon): array
    {
        $settings = DB::table('store_settings')->pluck('value', 'key')->all();
        $originLat = $this->coordinate($settings['store_latitude'] ?? null, -90, 90);
        $originLon = $this->coordinate($settings['store_longitude'] ?? null, -180, 180);
        if ($originLat === null || $originLon === null) {
            throw new RuntimeException('Lokasi toko belum dikonfigurasi. Hubungi pengelola toko.');
        }

        $routeMeters = $this->routeWithFallback($originLat, $originLon, $destinationLat, $destinationLon);

        if ($routeMeters === null) {
            $baseFee = max(0, (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)));
            $warning = 'Alamat belum terverifikasi. Ongkir Rp'.number_format($baseFee, 0, ',', '.').' adalah estimasi awal. Kurir akan mengonfirmasi biaya final.';
            return $this->buildBaseFeeQuote($address, $baseFee, $address, $warning);
        }

        $viaHaversine = $routeMeters['via_haversine'] ?? false;
        $source = 'coordinates+'.($viaHaversine ? 'haversine' : 'osrm');

        return $this->priceQuote(
            $address,
            (int) ceil($routeMeters['distance']),
            $settings,
            $address,
            $source,
            $destinationLat,
            $destinationLon,
            $viaHaversine,
        );
    }

    /**
     * Build the encrypted quote payload and public response from a distance.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function priceQuote(
        string $address,
        int $distanceMeters,
        array $settings,
        string $destinationLabel,
        string $source,
        ?float $lat = null,
        ?float $lon = null,
        bool $isEstimate = false,
    ): array {
        $distanceKm = $distanceMeters / 1000;
        $maxKm = (float) ($settings['delivery_max_km'] ?? 0);
        if ($maxKm > 0 && $distanceKm > $maxKm) {
            throw new RuntimeException('Alamat di luar jangkauan antar toko.');
        }

        $baseFee = max(0, (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)));
        $includedKm = max(0, (float) ($settings['delivery_included_km'] ?? 0));
        $feePerKm = max(0, (int) ($settings['delivery_fee_per_km'] ?? 0));
        $roundingKm = (float) ($settings['delivery_rounding_km'] ?? 0.5);
        if ($roundingKm <= 0 || $roundingKm > 10) {
            $roundingKm = 0.5;
        }
        $roundedKm = ceil($distanceKm / $roundingKm) * $roundingKm;
        $billableKm = max(0, $roundedKm - $includedKm);
        $fee = $baseFee + (int) ceil($billableKm * $feePerKm);
        if ($fee > 10000000) {
            throw new RuntimeException('Ongkir hasil perhitungan melampaui batas sistem. Hubungi pengelola toko.');
        }

        $normalizedAddress = Str::of($address)->squish()->lower()->toString();
        $ttl = $this->quoteTtlMinutes();
        $expiresAt = now()->addMinutes($ttl)->timestamp;

        $payload = [
            'id' => (string) Str::uuid(),
            'address_hash' => hash('sha256', $normalizedAddress),
            'distance_meters' => $distanceMeters,
            'fee' => $fee,
            'base_fee' => $baseFee,
            'included_km' => $includedKm,
            'fee_per_km' => $feePerKm,
            'rounding_km' => $roundingKm,
            'billable_km' => $billableKm,
            'is_estimate' => $isEstimate,
            'quote_source' => $source,
            'lat' => $lat,
            'lon' => $lon,
            'expires_at' => $expiresAt,
        ];

        $response = [
            'quote_id' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'distance_meters' => $distanceMeters,
            'distance_km' => round($distanceKm, 1),
            'fee' => $fee,
            'is_estimate' => $isEstimate,
            'is_base_fee' => false,
            'quote_source' => $source,
            'destination_label' => $destinationLabel,
            'lat' => $lat,
            'lon' => $lon,
            'expires_at' => now()->addMinutes($ttl)->toIso8601String(),
        ];

        if ($isEstimate) {
            $response['warning'] = 'Jarak dihitung sebagai estimasi karena rute jalan tidak tersedia. Ongkir final dikonfirmasi kurir.';
        }

        return $response;
    }

    /**
     * Build a base-fee estimate response (no distance measured).
     *
     * @return array<string, mixed>
     */
    private function buildBaseFeeQuote(string $address, int $baseFee, string $label, ?string $warning): array
    {
        $normalizedAddress = Str::of($address)->squish()->lower()->toString();
        $ttl = $this->quoteTtlMinutes();
        $expiresAt = now()->addMinutes($ttl)->timestamp;

        $payload = [
            'id' => (string) Str::uuid(),
            'address_hash' => hash('sha256', $normalizedAddress),
            'distance_meters' => null,
            'fee' => $baseFee,
            'base_fee' => $baseFee,
            'included_km' => 0,
            'fee_per_km' => 0,
            'rounding_km' => 0.5,
            'billable_km' => 0,
            'is_estimate' => true,
            'quote_source' => 'base_fee',
            'lat' => null,
            'lon' => null,
            'expires_at' => $expiresAt,
        ];

        return [
            'quote_id' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'distance_meters' => null,
            'distance_km' => null,
            'fee' => $baseFee,
            'is_estimate' => true,
            'is_base_fee' => true,
            'quote_source' => 'base_fee',
            'warning' => $warning ?? 'Alamat belum terverifikasi. Ongkir adalah estimasi awal.',
            'destination_label' => $label,
            'lat' => null,
            'lon' => null,
            'expires_at' => now()->addMinutes($ttl)->toIso8601String(),
        ];
    }

    /**
     * Validate a previously issued quote token against the current address.
     *
     * @return array<string, mixed>
     */
    public function validateQuote(string $token, string $address): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new RuntimeException('Estimasi ongkir kedaluwarsa atau tidak valid. Hitung ulang ongkir.');
        }

        $normalizedAddress = Str::of($address)->squish()->lower()->toString();
        if (! is_array($payload)
            || ($payload['expires_at'] ?? 0) < now()->timestamp
            || ! hash_equals((string) ($payload['address_hash'] ?? ''), hash('sha256', $normalizedAddress))) {
            throw new RuntimeException('Estimasi ongkir kedaluwarsa atau alamat berubah. Hitung ulang ongkir.');
        }

        $currentFee = $this->recalculateFee($payload);
        if ($currentFee !== (int) ($payload['fee'] ?? 0)) {
            throw new RuntimeException('Ongkir berubah. Hitung ulang ongkir untuk mendapatkan harga terbaru.');
        }

        return $payload;
    }

    /**
     * Recalculate fee from stored payload parameters using current settings.
     *
     * @param array<string, mixed> $payload
     */
    private function recalculateFee(array $payload): int
    {
        $settings = DB::table('store_settings')->pluck('value', 'key')->all();
        $baseFee = max(0, (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)));

        if (($payload['quote_source'] ?? '') === 'base_fee') {
            return $baseFee;
        }

        $distanceMeters = (int) ($payload['distance_meters'] ?? 0);
        if ($distanceMeters <= 0) {
            return $baseFee;
        }

        $distanceKm = $distanceMeters / 1000;
        $includedKm = max(0, (float) ($settings['delivery_included_km'] ?? 0));
        $feePerKm = max(0, (int) ($settings['delivery_fee_per_km'] ?? 0));
        $roundingKm = (float) ($settings['delivery_rounding_km'] ?? 0.5);
        if ($roundingKm <= 0 || $roundingKm > 10) {
            $roundingKm = 0.5;
        }
        $roundedKm = ceil($distanceKm / $roundingKm) * $roundingKm;
        $billableKm = max(0, $roundedKm - $includedKm);

        return $baseFee + (int) ceil($billableKm * $feePerKm);
    }

    private function quoteTtlMinutes(): int
    {
        $ttl = (int) DB::table('store_settings')->where('key', 'delivery_quote_ttl_minutes')->value('value');

        return $ttl > 0 ? $ttl : 15;
    }

    /**
     * Multi-provider geocoding chain: Nominatim → OpenCage → Mapbox.
     *
     * @return array{label: string, lat: ?float, lon: ?float, provider: string, suggestions: array}|null
     */
    private function geocodeChain(string $address): ?array
    {
        // 1. Nominatim — first attempt with full address
        $result = $this->nominatimSearch($address);
        if ($result !== null) {
            return $result;
        }

        // If Nominatim found nothing and the address contains a house-number
        // token, retry with the number stripped so we still get a street match.
        $stripped = $this->stripHouseNumber($address);
        if ($stripped !== null) {
            $result = $this->nominatimSearch($stripped, 5, skipRateLimit: true);
            if ($result !== null) {
                return $result;
            }
        }

        // 2. OpenCage (if key configured)
        $key = config('services.geocoding.opencage_key');
        if ($key !== null && $key !== '') {
            $result = $this->openCageSearch($address, $key);
            if ($result !== null) {
                return $result;
            }
        }

        // 3. Mapbox (if token configured)
        $token = config('services.geocoding.mapbox_token');
        if ($token !== null && $token !== '') {
            $result = $this->mapboxSearch($address, $token);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Remove a house-number segment from a free-form address so a Nominatim
     * retry still hits the street even when the full match fails.
     *
     * Returns null when the address has no house-number token to strip.
     */
    private function stripHouseNumber(string $address): ?string
    {
        // Indonesian patterns: "Jl. ... No. 10", "Jalan ... Nomor 12", or bare " ... No 7"
        $cleaned = preg_replace('/\b(?:No\.?|Nomor)\s*\d+\b/i', '', $address, -1, $count);
        if ($count === 0) {
            return null;
        }
        // tidy up double spaces / dangling commas
        $cleaned = preg_replace('/\s{2,}/', ' ', trim($cleaned));
        $cleaned = trim($cleaned, " ,");
        if ($cleaned === '') {
            return null;
        }
        return $cleaned;
    }

    /**
     * OpenCage geocoding (fallback 1).
     *
     * @return array{label: string, lat: ?float, lon: ?float, provider: string, suggestions: array}|null
     */
    private function openCageSearch(string $query, string $key): ?array
    {
        if (! $this->acquireProviderToken('opencage', self::OPENCAGE_MIN_INTERVAL_S)) {
            return null;
        }

        $response = Http::acceptJson()
            ->withToken($key)
            ->timeout(8)
            ->get('https://api.opencagedata.com/geocode/v1/json', [
                'q' => $query,
                'limit' => 5,
                'countrycode' => 'id',
            ]);

        if (! $response->successful()) {
            EventLogger::fail('quote.provider_error', [
                'provider' => 'opencage',
                'status' => $response->status(),
            ]);
            return null;
        }

        $results = $response->json('results');
        if (! is_array($results) || empty($results)) {
            return null;
        }

        $suggestions = [];
        foreach ($results as $row) {
            if (! is_array($row) || ! isset($row['geometry']['lat'], $row['geometry']['lng'], $row['formatted'])) {
                continue;
            }
            $lat = $this->coordinate($row['geometry']['lat'], -90, 90);
            $lon = $this->coordinate($row['geometry']['lng'], -180, 180);
            if ($lat === null || $lon === null) {
                continue;
            }
            $suggestions[] = [
                'label' => (string) $row['formatted'],
                'lat' => $lat,
                'lon' => $lon,
            ];
            if (count($suggestions) >= 5) {
                break;
            }
        }

        if (! $suggestions) {
            return null;
        }

        $first = $suggestions[0];

        return [
            'lat' => $first['lat'],
            'lon' => $first['lon'],
            'label' => $first['label'],
            'provider' => 'opencage',
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Mapbox geocoding (fallback 2).
     *
     * @return array{label: string, lat: ?float, lon: ?float, provider: string, suggestions: array}|null
     */
    private function mapboxSearch(string $query, string $token): ?array
    {
        if (! $this->acquireProviderToken('mapbox', self::MAPBOX_MIN_INTERVAL_S)) {
            return null;
        }

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout(8)
            ->get('https://api.mapbox.com/geocoding/v5/mapbox.places/'.urlencode($query).'.json', [
                'limit' => 5,
                'country' => 'id',
                'language' => 'id',
            ]);

        if (! $response->successful()) {
            EventLogger::fail('quote.provider_error', [
                'provider' => 'mapbox',
                'status' => $response->status(),
            ]);
            return null;
        }

        $features = $response->json('features');
        if (! is_array($features) || empty($features)) {
            return null;
        }

        $suggestions = [];
        foreach ($features as $feature) {
            if (! is_array($feature) || ! isset($feature['geometry']['coordinates'][0], $feature['geometry']['coordinates'][1], $feature['place_name'])) {
                continue;
            }
            $lon = $this->coordinate($feature['geometry']['coordinates'][0], -180, 180);
            $lat = $this->coordinate($feature['geometry']['coordinates'][1], -90, 90);
            if ($lat === null || $lon === null) {
                continue;
            }
            $suggestions[] = [
                'label' => (string) $feature['place_name'],
                'lat' => $lat,
                'lon' => $lon,
            ];
            if (count($suggestions) >= 5) {
                break;
            }
        }

        if (! $suggestions) {
            return null;
        }

        $first = $suggestions[0];

        return [
            'lat' => $first['lat'],
            'lon' => $first['lon'],
            'label' => $first['label'],
            'provider' => 'mapbox',
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Per-provider token bucket rate limiter using Cache.
     */
    private function acquireProviderToken(string $provider, int $minIntervalS): bool
    {
        $key = 'delivery:geocode:'.Str::slug($provider).':last-request';
        if ($provider === 'nominatim') {
            return Cache::add($key, now()->timestamp, now()->addSeconds($minIntervalS));
        }

        return Cache::add($key, now()->timestamp, now()->addSeconds(1));
    }

    /**
     * Route with Haversine fallback when OSRM fails.
     *
     * @return array{distance: float, via_haversine: bool}|null
     */
    private function routeWithFallback(float $originLat, float $originLon, float $destLat, float $destLon): ?array
    {
        $routeKey = 'delivery:route:'.hash('sha256', implode('|', [$originLat, $originLon, $destLat, $destLon]));
        $cached = Cache::get($routeKey);
        if (is_array($cached) && isset($cached['distance'])) {
            return ['distance' => $cached['distance'], 'via_haversine' => false];
        }

        try {
            $route = $this->route($originLon, $originLat, $destLon, $destLat);
            Cache::put($routeKey, ['distance' => $route['distance']], now()->addHours(12));

            return ['distance' => $route['distance'], 'via_haversine' => false];
        } catch (RuntimeException) {
            // OSRM failed; fall through to Haversine
        }

        $haversine = $this->haversineMeters($originLat, $originLon, $destLat, $destLon);
        if ($haversine === null) {
            return null;
        }

        return ['distance' => $haversine * self::ROUTING_ROAD_FACTOR, 'via_haversine' => true];
    }

    /**
     * Compute great-circle distance between two points in meters.
     */
    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): ?float
    {
        $r = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $r * $c;
    }

    private function nominatimSearch(string $query, int $limit = 5, bool $skipRateLimit = false): ?array
    {
        if (! $skipRateLimit && ! $this->acquireProviderToken('nominatim', self::NOMINATIM_MIN_INTERVAL_S)) {
            throw new RuntimeException('Layanan alamat sedang sibuk. Coba lagi dalam beberapa detik.');
        }

        $response = Http::acceptJson()
            ->withUserAgent('MenuOrderDeliveryQuote/1.0 ('.(string) config('app.url').')')
            ->timeout(8)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => $limit,
                'addressdetails' => 1,
                'countrycodes' => 'id',
                'accept-language' => 'id',
            ]);

        if (! $response->successful()) {
            EventLogger::fail('quote.provider_error', [
                'provider' => 'nominatim',
                'status' => $response->status(),
            ]);
            return null;
        }

        $rows = $response->json();
        if (! is_array($rows) || empty($rows)) {
            return null;
        }

        $suggestions = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['lat'], $row['lon'], $row['display_name'])) {
                continue;
            }
            $lat = $this->coordinate($row['lat'], -90, 90);
            $lon = $this->coordinate($row['lon'], -180, 180);
            if ($lat === null || $lon === null) {
                continue;
            }

            $label = $this->formatSuggestionLabel($row);
            $suggestions[] = [
                'label' => $label,
                'lat' => $lat,
                'lon' => $lon,
            ];
            if (count($suggestions) >= $limit) {
                break;
            }
        }

        if (! $suggestions) {
            return null;
        }

        $first = $suggestions[0];

        return [
            'lat' => $first['lat'],
            'lon' => $first['lon'],
            'label' => $first['label'],
            'provider' => 'nominatim',
            'suggestions' => $suggestions,
        ];
    }

    private function formatSuggestionLabel(array $row): string
    {
        $addr = $row['address'] ?? [];

        $road = (string) ($addr['road'] ?? '');
        $houseNumber = (string) ($addr['house_number'] ?? '');
        $suburb = (string) ($addr['suburb'] ?? ''); 
        $city = (string) ($addr['city'] ?? ($addr['town'] ?? ($addr['municipality'] ?? '')));
        $district = (string) ($addr['district'] ?? ''); 

        $parts = array_values(array_filter([
            $road && $houseNumber ? $road.' No. '.$houseNumber : $road,
            $suburb,
            $district,
            $city,
        ]));

        return $parts ? implode(', ', $parts) : (string) $row['display_name'];
    }

    private function route(float $originLon, float $originLat, float $destLon, float $destLat): array
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s',
            $originLon,
            $originLat,
            $destLon,
            $destLat
        );
        $response = Http::acceptJson()->timeout(8)->get($url, ['overview' => 'false', 'steps' => 'false']);
        $distance = $response->json('routes.0.distance');
        if (! $response->successful() || $response->json('code') !== 'Ok' || ! is_numeric($distance) || $distance <= 0) {
            EventLogger::fail('quote.provider_error', [
                'provider' => 'osrm',
                'status' => $response->status(),
                'code' => $response->json('code'),
            ]);
            throw new RuntimeException('Rute pengantaran tidak dapat dihitung.');
        }

        return ['distance' => (float) $distance];
    }

    public function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;

        return $number >= $min && $number <= $max ? $number : null;
    }
}
