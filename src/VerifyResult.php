<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of verifyOtp() (POST /v3/verify).
 */
final class VerifyResult
{
    /**
     * @param string $reason "" when $verified is true, "mismatch" when the code was wrong. A mismatch is HTTP 200 and therefore NOT an exception from verifyOtp(). Expired / locked / already-used transactions come back as ApiException (OTP_EXPIRED, TOO_MANY_ATTEMPTS, ALREADY_USED) instead.
     */
    public function __construct(
        public readonly string $otpId,
        public readonly bool $verified,
        public readonly string $reason,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            otpId: Scalars::str($data, 'otp_id'),
            verified: Scalars::bool($data, 'verified'),
            reason: Scalars::str($data, 'reason'),
        );
    }
}
