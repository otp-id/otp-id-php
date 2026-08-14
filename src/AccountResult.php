<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Success payload of account() (GET /v3/account). It never contains
 * credentials.
 */
final class AccountResult
{
    public string $merchantId;

    public string $name;

    public string $brandName;

    public string $brandEmail;

    public string $email;

    /** @var int current credit balance */
    public int $saldo;

    public function __construct(
        string $merchantId,
        string $name,
        string $brandName,
        string $brandEmail,
        string $email,
        int $saldo
    ) {
        $this->merchantId = $merchantId;
        $this->name = $name;
        $this->brandName = $brandName;
        $this->brandEmail = $brandEmail;
        $this->email = $email;
        $this->saldo = $saldo;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Scalars::str($data, 'merchant_id'),
            Scalars::str($data, 'name'),
            Scalars::str($data, 'brand_name'),
            Scalars::str($data, 'brand_email'),
            Scalars::str($data, 'email'),
            Scalars::int($data, 'saldo')
        );
    }
}
