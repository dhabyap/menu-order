<?php

namespace App\Support;

/**
 * Single source of truth for customer order statuses.
 */
class OrderStatus
{
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    /**
     * Allowed status values mapped to their Indonesian labels.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::PENDING => 'Menunggu',
            self::PROCESSING => 'Diproses',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        ];
    }

    /**
     * Ordered statuses used by the customer tracking timeline.
     *
     * @return array<int, string>
     */
    public static function flow(): array
    {
        return [self::PENDING, self::PROCESSING, self::COMPLETED];
    }

    /**
     * Human label for a stored status value.
     */
    public static function label(string $status): string
    {
        return self::labels()[$status] ?? ucfirst($status);
    }
}
