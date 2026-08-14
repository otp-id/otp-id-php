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
    public string $waNumber;

    public string $message;

    public string $waLink;

    public string $expiresAt;

    public string $prefix;

    public int $otpLength;

    public function __construct(
        string $waNumber,
        string $message,
        string $waLink,
        string $expiresAt,
        string $prefix,
        int $otpLength
    ) {
        $this->waNumber = $waNumber;
        $this->message = $message;
        $this->waLink = $waLink;
        $this->expiresAt = $expiresAt;
        $this->prefix = $prefix;
        $this->otpLength = $otpLength;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Scalars::str($data, 'wa_number'),
            Scalars::str($data, 'message'),
            Scalars::str($data, 'wa_link'),
            Scalars::str($data, 'expires_at'),
            Scalars::str($data, 'prefix'),
            Scalars::int($data, 'otp_length')
        );
    }
}
