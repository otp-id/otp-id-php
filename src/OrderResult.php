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
    public string $otpId;

    /** @var string pending | sent | success | failed */
    public string $status;

    public string $channel;

    public string $number;

    public int $price;

    /** @var int remaining credit after this transaction; stays unchanged when delivery failed (status "failed") or on an idempotency replay */
    public int $lastBalance;

    /** @var string "YYYY-MM-DD HH:MM:SS" in WIB (UTC+7); kept as a string, the SDK does not parse server datetimes */
    public string $expiresAt;

    public ?Verification $verification;

    /** @var ?DeliveryFailure present only when $status is "failed"; the public, classified delivery-failure reason (never the raw vendor error) */
    public ?DeliveryFailure $failure;

    public function __construct(
        string $otpId,
        string $status,
        string $channel,
        string $number,
        int $price,
        int $lastBalance,
        string $expiresAt,
        ?Verification $verification,
        ?DeliveryFailure $failure = null
    ) {
        $this->otpId = $otpId;
        $this->status = $status;
        $this->channel = $channel;
        $this->number = $number;
        $this->price = $price;
        $this->lastBalance = $lastBalance;
        $this->expiresAt = $expiresAt;
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
            Scalars::int($data, 'price'),
            Scalars::int($data, 'last_balance'),
            Scalars::str($data, 'expires_at'),
            \is_array($verification) ? Verification::fromArray($verification) : null,
            \is_array($failure) ? DeliveryFailure::fromArray($failure) : null
        );
    }
}
