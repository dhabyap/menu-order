<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DeliveryQuoteService
{
    public function quote(string $address): array
    {
        $settings = DB::table('store_settings')->pluck('value', 'key');
        $originLat = $this->coordinate($settings['store_latitude'] ?? null, -90, 90);
        $originLon = $this->coordinate($settings['store_longitude'] ?? null, -180, 180);
        if ($originLat === null || $originLon === null) {
            throw new RuntimeException('Lokasi toko belum dikonfigurasi. Hubungi pengelola toko.');
        }

        $normalizedAddress = Str::of($address)->squish()->lower()->toString();
        $cacheKey = 'delivery:geocode:'.hash('sha256', $normalizedAddress);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $destination = $cached;
        } else {
            $destination = $this->geocodeWithRetry($address);
            Cache::put($cacheKey, $destination, now()->addDays(7));
        }

        $routeKey = 'delivery:route:'.hash('sha256', implode('|', [$originLat, $originLon, $destination['lat'], $destination['lon']]));
        $route = Cache::get($routeKey);
        if (! is_array($route)) {
            $route = $this->route($originLon, $originLat, $destination['lon'], $destination['lat']);
            Cache::put($routeKey, $route, now()->addHours(12));
        }

        $distanceMeters = (int) ceil($route['distance']);
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

        $expiresAt = now()->addMinutes(15)->timestamp;
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
            'expires_at' => $expiresAt,
        ];

        $response = [
            'quote_id' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'distance_meters' => $distanceMeters,
            'distance_km' => round($distanceKm, 1),
            'fee' => $fee,
            'destination_label' => $destination['label'],
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];

        if (! empty($destination['suggestions'])) {
            $response['suggestions'] = $destination['suggestions'];
        }

        return $response;
    }

    public function validateQuote(string $token, string $address): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('Estimasi ongkir kedaluwarsa atau tidak valid. Hitung ulang ongkir.');
        }

        $normalizedAddress = Str::of($address)->squish()->lower()->toString();
        if (! is_array($payload)
            || ($payload['expires_at'] ?? 0) < now()->timestamp
            || ! hash_equals((string) ($payload['address_hash'] ?? ''), hash('sha256', $normalizedAddress))) {
            throw new RuntimeException('Estimasi ongkir kedaluwarsa atau alamat berubah. Hitung ulang ongkir.');
        }

        return $payload;
    }

    private function geocodeWithRetry(string $address): array
    {
        $candidates = $this->nominatimSearch($address, 5);
        if ($candidates !== null) {
            return $candidates;
        }

        $retryQuery = preg_replace('/\b\d+[a-zA-Z]*\b/', '', $address, 1);
        $retryQuery = Str::of($retryQuery)->squish()->toString();
        if ($retryQuery !== '' && $retryQuery !== $address) {
            $retry = $this->nominatimSearch($retryQuery, 5);
            if ($retry !== null) {
                return $retry;
            }
        }

        throw new RuntimeException('Alamat tidak ditemukan. Gunakan nama jalan, kelurahan, kecamatan, dan kota.');
    }

    private function nominatimSearch(string $query, int $limit): ?array
    {
        if (! Cache::add('delivery:nominatim:last-request', now()->timestamp, now()->addSeconds(3))) {
            throw new RuntimeException('Layanan pencarian alamat sedang sibuk. Coba hitung ongkir lagi sebentar.');
        }

        $q = str_contains(strtolower($query), 'bandung') ? $query : $query.', Kota Bandung';

        $response = Http::acceptJson()
            ->withUserAgent('MenuOrderDeliveryQuote/1.0 ('.(string) config('app.url').')')
            ->timeout(8)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $q,
                'format' => 'jsonv2',
                'limit' => $limit,
                'addressdetails' => 1,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Pencarian alamat sementara tidak tersedia. Coba lagi nanti.');
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
            'suggestions' => $suggestions,
        ];
    }

    private function formatSuggestionLabel(array $row): string
    {
        $addr = $row['address'] ?? [];

        $road = (string) ($addr['road'] ?? '');
        $houseNumber = (string) ($addr['house_number'] ?? '');
        $suburb = (string) ($addr['suburb'] ?? ''); // kelurahan
        $city = (string) ($addr['city'] ?? ($addr['town'] ?? ($addr['municipality'] ?? '')));
        $district = (string) ($addr['district'] ?? ''); // kecamatan

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
            throw new RuntimeException('Rute pengantaran tidak dapat dihitung. Periksa alamat atau coba lagi nanti.');
        }

        return ['distance' => (float) $distance];
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        return $number >= $min && $number <= $max ? $number : null;
    }
}
