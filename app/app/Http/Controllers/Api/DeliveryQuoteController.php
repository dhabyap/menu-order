<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryQuoteRequest;
use App\Services\DeliveryQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeliveryQuoteController extends Controller
{
    public function __invoke(DeliveryQuoteRequest $request, DeliveryQuoteService $quotes): JsonResponse
    {
        try {
            $address = $request->validated('address');
            $selectedLat = $request->input('selected_lat');
            $selectedLon = $request->input('selected_lon');
            $fallback = (bool) $request->boolean('fallback');

            if ($selectedLat !== null && $selectedLon !== null) {
                $settings = DB::table('store_settings')->pluck('value', 'key');
                $originLat = $quotes->coordinate($settings['store_latitude'] ?? null, -90, 90);
                $originLon = $quotes->coordinate($settings['store_longitude'] ?? null, -180, 180);
                if ($originLat === null || $originLon === null) {
                    throw new RuntimeException('Lokasi toko belum dikonfigurasi. Hubungi pengelola toko.');
                }
                $distanceMeters = (int) ceil($quotes->route($originLon, $originLat, (float) $selectedLon, (float) $selectedLat)['distance']);
                $distanceKm = $distanceMeters / 1000;
                $maxKm = (float) ($settings['delivery_max_km'] ?? 0);
                if ($maxKm > 0 && $distanceKm > $maxKm) {
                    throw new RuntimeException('Alamat di luar jangkauan antar toko.');
                }
                $baseFee = max(0, (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)));
                $includedKm = max(0, (float) ($settings['delivery_included_km'] ?? 0));
                $feePerKm = max(0, (int) ($settings['delivery_fee_per_km'] ?? 0));
                $roundingKm = (float) ($settings['delivery_rounding_km'] ?? 0.5);
                if ($roundingKm <= 0 || $roundingKm > 10) { $roundingKm = 0.5; }
                $roundedKm = ceil($distanceKm / $roundingKm) * $roundingKm;
                $billableKm = max(0, $roundedKm - $includedKm);
                $fee = $baseFee + (int) ceil($billableKm * $feePerKm);
                if ($fee > 10000000) {
                    throw new RuntimeException('Ongkir hasil perhitungan melampaui batas sistem. Hubungi pengelola toko.');
                }

                return response()->json([
                    'quote_id' => (string) Str::uuid(),
                    'distance_meters' => $distanceMeters,
                    'distance_km' => round($distanceKm, 1),
                    'fee' => $fee,
                    'destination_label' => $address,
                    'expires_at' => now()->addMinutes(15)->toIso8601String(),
                ]);
            }

            return response()->json($quotes->quote($address, $fallback));
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            report($error);
            return response()->json(['message' => 'Estimasi ongkir sementara gagal. Coba lagi nanti.'], 503);
        }
    }
}
