<?php

declare(strict_types=1);

namespace OtpId\Exception;

/**
 * Thrown for any non-success API response. Match on getErrorCode() and
 * switch on the OtpId\ErrorCode constants.
 */
class ApiException extends OtpIdException
{
    private string $errorCode;

    private int $httpStatus;

    /** @var array<string, mixed>|null */
    private ?array $details;

    /**
     * @param array<string, mixed>|null $details e.g. {"existing_otp_id": "..."} on DUPLICATE_EXTERNAL_ID
     */
    public function __construct(string $errorCode, string $message, int $httpStatus, ?array $details = null)
    {
        parent::__construct(sprintf('%s: %s (http %d)', $errorCode, $message, $httpStatus));

        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }
}
