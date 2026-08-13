<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of createTopup() (POST /v3/topups). $paymentUrl is a
 * signed OTP.ID payment page that opens without a dashboard login.
 */
final class TopupResult
{
    /**
     * @param int $paymentTotal amount + admin fee; display as-is
     */
    public function __construct(
        public readonly string $topupId,
        public readonly string $paymentUrl,
        public readonly string $paymentHash,
        public readonly int $amount,
        public readonly int $paymentTotal,
        public readonly int $paymentMethodId,
        public readonly string $paymentMethod,
        public readonly string $paymentType,
        public readonly string $paymentExpiredAt,
        public readonly string $status,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            topupId: Scalars::str($data, 'topup_id'),
            paymentUrl: Scalars::str($data, 'payment_url'),
            paymentHash: Scalars::str($data, 'payment_hash'),
            amount: Scalars::int($data, 'amount'),
            paymentTotal: Scalars::int($data, 'payment_total'),
            paymentMethodId: Scalars::int($data, 'payment_method_id'),
            paymentMethod: Scalars::str($data, 'payment_method'),
            paymentType: Scalars::str($data, 'payment_type'),
            paymentExpiredAt: Scalars::str($data, 'payment_expired_at'),
            status: Scalars::str($data, 'status'),
        );
    }
}
