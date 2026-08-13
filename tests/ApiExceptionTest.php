<?php

declare(strict_types=1);

namespace OtpId\Tests;

use OtpId\ErrorCode;
use OtpId\Exception\ApiException;
use OtpId\Exception\OtpIdException;
use PHPUnit\Framework\TestCase;

final class ApiExceptionTest extends TestCase
{
    public function testMessageFormat(): void
    {
        $exception = new ApiException(ErrorCode::INSUFFICIENT_BALANCE, 'saldo tidak cukup', 402);

        self::assertSame('INSUFFICIENT_BALANCE: saldo tidak cukup (http 402)', $exception->getMessage());
    }

    public function testGetters(): void
    {
        $details = ['existing_otp_id' => 'OTP20260807ABCD000001'];
        $exception = new ApiException(ErrorCode::DUPLICATE_EXTERNAL_ID, 'duplicate', 409, $details);

        self::assertSame(ErrorCode::DUPLICATE_EXTERNAL_ID, $exception->getErrorCode());
        self::assertSame(409, $exception->getHttpStatus());
        self::assertSame($details, $exception->getDetails());
    }

    public function testDetailsDefaultToNull(): void
    {
        $exception = new ApiException(ErrorCode::OTP_EXPIRED, 'expired', 422);

        self::assertNull($exception->getDetails());
    }

    public function testIsInstanceOfOtpIdException(): void
    {
        $exception = new ApiException(ErrorCode::OTP_EXPIRED, 'expired', 422);

        self::assertInstanceOf(OtpIdException::class, $exception);
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testErrorCodeValues(): void
    {
        // Guards the constants against typos — values are the API contract.
        // Kept in sync with otp-id-go/errors_test.go::TestErrorCodeValues.
        $cases = [
            ErrorCode::UNAUTHORIZED => 'UNAUTHORIZED',
            ErrorCode::VALIDATION_ERROR => 'VALIDATION_ERROR',
            ErrorCode::INVALID_CHANNEL => 'INVALID_CHANNEL',
            ErrorCode::INVALID_NUMBER => 'INVALID_NUMBER',
            ErrorCode::INSUFFICIENT_BALANCE => 'INSUFFICIENT_BALANCE',
            ErrorCode::OTP_NOT_FOUND => 'OTP_NOT_FOUND',
            ErrorCode::DUPLICATE_EXTERNAL_ID => 'DUPLICATE_EXTERNAL_ID',
            ErrorCode::OTP_EXPIRED => 'OTP_EXPIRED',
            ErrorCode::TOO_MANY_ATTEMPTS => 'TOO_MANY_ATTEMPTS',
            ErrorCode::ALREADY_USED => 'ALREADY_USED',
            ErrorCode::RATE_LIMITED => 'RATE_LIMITED',
            ErrorCode::DESTINATION_RATE_LIMITED => 'DESTINATION_RATE_LIMITED',
            ErrorCode::CHANNEL_UNAVAILABLE => 'CHANNEL_UNAVAILABLE',
            ErrorCode::IP_NOT_ALLOWED => 'IP_NOT_ALLOWED',
            ErrorCode::INTERNAL_ERROR => 'INTERNAL_ERROR',
            ErrorCode::INVALID_RESPONSE => 'INVALID_RESPONSE',
        ];

        foreach ($cases as $got => $want) {
            self::assertSame($want, $got);
        }
    }
}
