<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of otpStatus() (GET /v3/otp/{otp_id}).
 */
final class StatusResult
{
    public string $otpId;

    /** @var string sent | success | failed | pending | verified */
    public string $status;

    public string $channel;

    public string $number;

    public int $attempts;

    public string $expiresAt;

    /** @var string "" until verified */
    public string $verifiedAt;

    public int $price;

    /** @var ?Verification present for not-yet-verified misscall transactions (prefix field) and for pending, not-yet-expired whatsapp_inbound transactions (wa_number/message/wa_link/expires_at fields), so polling clients can build their UI */
    public ?Verification $verification;

    /** @var ?DeliveryFailure present only when $status is "failed"; the public, classified delivery-failure reason (never the raw vendor error) */
    public ?DeliveryFailure $failure;

    public function __construct(
        string $otpId,
        string $status,
        string $channel,
        string $number,
        int $attempts,
        string $expiresAt,
        string $verifiedAt,
        int $price,
        ?Verification $verification,
        ?DeliveryFailure $failure = null
    ) {
        $this->otpId = $otpId;
        $this->status = $status;
        $this->channel = $channel;
        $this->number = $number;
        $this->attempts = $attempts;
        $this->expiresAt = $expiresAt;
        $this->verifiedAt = $verifiedAt;
        $this->price = $price;
        $this->verification = $verification;
        $this->failure = $failure;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $verification = $data['verification'] ?? null;
        $failure = $data['failure'] ?? null;

        return new self(
            Scalars::str($data, 'otp_id'),
            Scalars::str($data, 'status'),
            Scalars::str($data, 'channel'),
            Scalars::str($data, 'number'),
            Scalars::int($data, 'attempts'),
            Scalars::str($data, 'expires_at'),
            Scalars::str($data, 'verified_at'),
            Scalars::int($data, 'price'),
            \is_array($verification) ? Verification::fromArray($verification) : null,
            \is_array($failure) ? DeliveryFailure::fromArray($failure) : null
        );
    }
}
