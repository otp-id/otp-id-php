<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Flat superset of the per-channel `verification` response block. Which
 * fields are set depends on the channel: whatsapp_inbound fills
 * waNumber/message/waLink/expiresAt; misscall fills prefix (and otpLength
 * on order responses). All other channels have no verification block at
 * all (the containing result's $verification is null).
 */
final class Verification
{
    public function __construct(
        public readonly string $waNumber,
        public readonly string $message,
        public readonly string $waLink,
        public readonly string $expiresAt,
        public readonly string $prefix,
        public readonly int $otpLength,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            waNumber: Scalars::str($data, 'wa_number'),
            message: Scalars::str($data, 'message'),
            waLink: Scalars::str($data, 'wa_link'),
            expiresAt: Scalars::str($data, 'expires_at'),
            prefix: Scalars::str($data, 'prefix'),
            otpLength: Scalars::int($data, 'otp_length'),
        );
    }
}
