<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Internal\Scalars;

/**
 * Public reason a transaction has `status: "failed"` — set on OrderResult
 * and StatusResult only when the transaction failed to deliver. The raw
 * vendor/provider error is never exposed; `$code` is one of the FailureCode
 * constants (forward-compatible — treat unknown codes as a generic
 * failure) and `$message` is a human-readable Indonesian description safe
 * to show a merchant.
 */
final class DeliveryFailure
{
    public string $code;

    public string $message;

    public function __construct(string $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Scalars::str($data, 'code'),
            Scalars::str($data, 'message')
        );
    }
}
