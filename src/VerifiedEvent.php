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
    /** @var string always "otp.verified" */
    public string $event;

    public string $otpId;

    /** @var string "" when the merchant sent no external_id */
    public string $externalId;

    /** @var string delivery channel value, e.g. "whatsapp" */
    public string $channel;

    public string $number;

    /** @var string "YYYY-MM-DD HH:MM:SS" in WIB (UTC+7) */
    public string $verifiedAt;

    public function __construct(
        string $event,
        string $otpId,
        string $externalId,
        string $channel,
        string $number,
        string $verifiedAt
    ) {
        $this->event = $event;
        $this->otpId = $otpId;
        $this->externalId = $externalId;
        $this->channel = $channel;
        $this->number = $number;
        $this->verifiedAt = $verifiedAt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Scalars::str($data, 'event'),
            Scalars::str($data, 'otp_id'),
            Scalars::str($data, 'external_id'),
            Scalars::str($data, 'channel'),
            Scalars::str($data, 'number'),
            Scalars::str($data, 'verified_at')
        );
    }
}
