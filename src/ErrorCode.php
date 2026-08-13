<?php

declare(strict_types=1);

namespace OtpId;

/**
 * Error codes returned by the OTP.ID V3 API (kept in sync with the server).
 */
final class ErrorCode
{
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const INVALID_CHANNEL = 'INVALID_CHANNEL';
    public const INVALID_NUMBER = 'INVALID_NUMBER';
    public const INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    public const OTP_NOT_FOUND = 'OTP_NOT_FOUND';
    public const DUPLICATE_EXTERNAL_ID = 'DUPLICATE_EXTERNAL_ID';
    public const OTP_EXPIRED = 'OTP_EXPIRED';
    public const TOO_MANY_ATTEMPTS = 'TOO_MANY_ATTEMPTS';
    public const ALREADY_USED = 'ALREADY_USED';
    public const RATE_LIMITED = 'RATE_LIMITED';
    public const DESTINATION_RATE_LIMITED = 'DESTINATION_RATE_LIMITED';
    public const CHANNEL_UNAVAILABLE = 'CHANNEL_UNAVAILABLE';
    public const IP_NOT_ALLOWED = 'IP_NOT_ALLOWED';
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * INVALID_RESPONSE is produced by the SDK itself (never by the server)
     * when a response body cannot be decoded as a V3 JSON envelope.
     */
    public const INVALID_RESPONSE = 'INVALID_RESPONSE';

    private function __construct()
    {
        // Static constant holder — not instantiable.
    }
}
