<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of otpStatus() (GET /v3/otp/{otp_id}).
 */
final class StatusResult
{
    /**
     * @param string $status sent | success | failed | pending | verified
     * @param string $verifiedAt "" until verified
     * @param ?Verification $verification only present for not-yet-verified misscall transactions (prefix field), so polling clients can build their UI
     */
    public function __construct(
        public readonly string $otpId,
        public readonly string $status,
        public readonly string $channel,
        public readonly string $number,
        public readonly int $attempts,
        public readonly string $expiresAt,
        public readonly string $verifiedAt,
        public readonly int $price,
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
            attempts: Scalars::int($data, 'attempts'),
            expiresAt: Scalars::str($data, 'expires_at'),
            verifiedAt: Scalars::str($data, 'verified_at'),
            price: Scalars::int($data, 'price'),
            verification: \is_array($verification) ? Verification::fromArray($verification) : null,
        );
    }
}
