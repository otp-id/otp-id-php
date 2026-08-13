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
    /**
     * @param int $saldo current credit balance
     */
    public function __construct(
        public readonly string $merchantId,
        public readonly string $name,
        public readonly string $brandName,
        public readonly string $brandEmail,
        public readonly string $email,
        public readonly int $saldo,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            merchantId: Scalars::str($data, 'merchant_id'),
            name: Scalars::str($data, 'name'),
            brandName: Scalars::str($data, 'brand_name'),
            brandEmail: Scalars::str($data, 'brand_email'),
            email: Scalars::str($data, 'email'),
            saldo: Scalars::int($data, 'saldo'),
        );
    }
}
