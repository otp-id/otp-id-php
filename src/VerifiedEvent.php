<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Payload of the `otp.verified` webhook, returned by
 * Webhook::parseVerifiedEvent().
 */
final class VerifiedEvent
{
    /**
     * @param string $event      always "otp.verified"
     * @param string $externalId "" when the merchant sent no external_id
     * @param string $channel    delivery channel value, e.g. "whatsapp"
     * @param string $verifiedAt "YYYY-MM-DD HH:MM:SS" in WIB (UTC+7)
     */
    public function __construct(
        public readonly string $event,
        public readonly string $otpId,
        public readonly string $externalId,
        public readonly string $channel,
        public readonly string $number,
        public readonly string $verifiedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            event: Scalars::str($data, 'event'),
            otpId: Scalars::str($data, 'otp_id'),
            externalId: Scalars::str($data, 'external_id'),
            channel: Scalars::str($data, 'channel'),
            number: Scalars::str($data, 'number'),
            verifiedAt: Scalars::str($data, 'verified_at'),
        );
    }
}
