<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of requestOtp() (POST /v3/request) and sendOtp()
 * (POST /v3/send).
 */
final class OrderResult
{
    /**
     * @param string $status pending | sent | success | failed
     * @param string $expiresAt "YYYY-MM-DD HH:MM:SS" in WIB (UTC+7); kept as a string, the SDK does not parse server datetimes
     * @param int $lastBalance remaining credit after this transaction; stays unchanged when delivery failed (status "failed") or on an idempotency replay
     */
    public function __construct(
        public readonly string $otpId,
        public readonly string $status,
        public readonly string $channel,
        public readonly string $number,
        public readonly int $price,
        public readonly int $lastBalance,
        public readonly string $expiresAt,
        public readonly ?Verification $verification,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $verification = $data['verification'] ?? null;

        return new self(
            otpId: Scalars::str($data, 'otp_id'),
            status: Scalars::str($data, 'status'),
            channel: Scalars::str($data, 'channel'),
            number: Scalars::str($data, 'number'),
            price: Scalars::int($data, 'price'),
            lastBalance: Scalars::int($data, 'last_balance'),
            expiresAt: Scalars::str($data, 'expires_at'),
            verification: \is_array($verification) ? Verification::fromArray($verification) : null,
        );
    }
}
