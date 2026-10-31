<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for store operating hours.
 *
 * The same calculation is used by the catalog (status badge), the checkout
 * endpoint (server-side guard), and the admin dashboard so customers never
 * see a different status than the one enforced when the order is saved.
 */
class StoreHours
{
    public function __construct(
        private readonly ?string $open,
        private readonly ?string $close,
        private readonly string $timezone = 'Asia/Jakarta',
    ) {}

    /**
     * Build the schedule from the flat store_settings key/value array.
     *
     * @param  array<string, mixed>  $settings
     */
    public static function fromSettings(array $settings): self
    {
        return new self(
            self::normalizeTime($settings['open_time'] ?? null),
            self::normalizeTime($settings['close_time'] ?? null),
            (string) ($settings['timezone'] ?? config('store.timezone', 'Asia/Jakarta')),
        );
    }

    public function openTime(): ?string
    {
        return $this->open;
    }

    public function closeTime(): ?string
    {
        return $this->close;
    }

    /**
     * A store without configured hours accepts orders at any time.
     */
    public function isAlwaysOpen(): bool
    {
        return $this->open === null || $this->close === null;
    }

    public function isClosedAllDay(): bool
    {
        return ! $this->isAlwaysOpen() && $this->open === $this->close;
    }

    public function isOpen(?CarbonInterface $now = null): bool
    {
        if ($this->isAlwaysOpen()) {
            return true;
        }

        if ($this->isClosedAllDay()) {
            return false;
        }

        $time = $this->localNow($now)->format('H:i');

        if ($this->open < $this->close) {
            return $time >= $this->open && $time < $this->close;
        }

        // Operating hours that cross midnight, for example 18:00 - 02:00.
        return $time >= $this->open || $time < $this->close;
    }

    /**
     * Short label for the catalog status badge.
     */
    public function label(?CarbonInterface $now = null): string
    {
        if ($this->isAlwaysOpen()) {
            return 'Buka setiap hari';
        }

        if ($this->isClosedAllDay()) {
            return 'Tutup hari ini';
        }

        if ($this->isOpen($now)) {
            return "Buka sampai {$this->close}";
        }

        $time = $this->localNow($now)->format('H:i');

        if ($this->open < $this->close && $time >= $this->close) {
            return "Tutup · Buka besok {$this->open}";
        }

        return "Tutup · Buka {$this->open}";
    }

    /**
     * Longer explanation shown near the checkout button.
     */
    public function message(?CarbonInterface $now = null): string
    {
        if ($this->isAlwaysOpen()) {
            return 'Pesanan dapat dikirim kapan saja.';
        }

        if ($this->isClosedAllDay()) {
            return 'Toko sedang tutup hari ini.';
        }

        if ($this->isOpen($now)) {
            return "Pesanan diproses selama jam operasional {$this->open}–{$this->close}.";
        }

        return "Toko sedang tutup. Jam buka: {$this->open} - {$this->close}.";
    }

    /**
     * Payload shared by the catalog configuration endpoint.
     *
     * @return array<string, mixed>
     */
    public function toArray(?CarbonInterface $now = null): array
    {
        return [
            'is_open' => $this->isOpen($now),
            'status_label' => $this->label($now),
            'status_message' => $this->message($now),
            'open_time' => $this->open,
            'close_time' => $this->close,
            'timezone' => $this->timezone,
        ];
    }

    private function localNow(?CarbonInterface $now = null): CarbonInterface
    {
        return $now
            ? Carbon::instance($now)->setTimezone($this->timezone)
            : Carbon::now($this->timezone);
    }

    private static function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : substr($value, 0, 5);
    }
}
