<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of createTopup() (POST /v3/topups). $paymentUrl is a
 * signed OTP.ID payment page that opens without a dashboard login.
 */
final class TopupResult
{
    public string $topupId;

    public string $paymentUrl;

    public string $paymentHash;

    public int $amount;

    /** @var int amount + admin fee; display as-is */
    public int $paymentTotal;

    public int $paymentMethodId;

    public string $paymentMethod;

    public string $paymentType;

    public string $paymentExpiredAt;

    public string $status;

    public function __construct(
        string $topupId,
        string $paymentUrl,
        string $paymentHash,
        int $amount,
        int $paymentTotal,
        int $paymentMethodId,
        string $paymentMethod,
        string $paymentType,
        string $paymentExpiredAt,
        string $status
    ) {
        $this->topupId = $topupId;
        $this->paymentUrl = $paymentUrl;
        $this->paymentHash = $paymentHash;
        $this->amount = $amount;
        $this->paymentTotal = $paymentTotal;
        $this->paymentMethodId = $paymentMethodId;
        $this->paymentMethod = $paymentMethod;
        $this->paymentType = $paymentType;
        $this->paymentExpiredAt = $paymentExpiredAt;
        $this->status = $status;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Scalars::str($data, 'topup_id'),
            Scalars::str($data, 'payment_url'),
            Scalars::str($data, 'payment_hash'),
            Scalars::int($data, 'amount'),
            Scalars::int($data, 'payment_total'),
            Scalars::int($data, 'payment_method_id'),
            Scalars::str($data, 'payment_method'),
            Scalars::str($data, 'payment_type'),
            Scalars::str($data, 'payment_expired_at'),
            Scalars::str($data, 'status')
        );
    }
}
