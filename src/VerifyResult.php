<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of verifyOtp() (POST /v3/verify).
 */
final class VerifyResult
{
    public string $otpId;

    public bool $verified;

    /** @var string "" when $verified is true, "mismatch" when the code was wrong. A mismatch is HTTP 200 and therefore NOT an exception from verifyOtp(). Expired / locked / already-used transactions come back as ApiException (OTP_EXPIRED, TOO_MANY_ATTEMPTS, ALREADY_USED) instead. */
    public string $reason;

    public function __construct(string $otpId, bool $verified, string $reason)
    {
        $this->otpId = $otpId;
        $this->verified = $verified;
        $this->reason = $reason;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Scalars::str($data, 'otp_id'),
            Scalars::bool($data, 'verified'),
            Scalars::str($data, 'reason')
        );
    }
}
