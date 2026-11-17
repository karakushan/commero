<?php

namespace Commero\Payments;

final class PaymentStatus
{
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::PENDING, self::PROCESSING, self::PAID, self::FAILED, self::REFUNDED];
    }

    public static function color(?string $status): string
    {
        return match ($status) {
            self::PAID => 'success',
            self::PROCESSING => 'info',
            self::FAILED => 'danger',
            self::REFUNDED => 'gray',
            default => 'warning',
        };
    }
}
