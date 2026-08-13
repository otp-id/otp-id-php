<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Exception\ApiException;
use OtpId\Exception\InvalidSignatureException;
use OtpId\Exception\StaleTimestampException;
use OtpId\Exception\UnexpectedEventException;
use OtpId\Internal\Scalars;

/**
 * Verifies and parses the `otp.verified` webhook OTP.ID sends after a
 * transaction is confirmed. Mirrors otp-id-go/webhook.go — see that file
 * for the reference contract.
 */
final class Webhook
{
    /** Default freshness window, in seconds, accepted by parseVerifiedEvent(). */
    public const DEFAULT_TOLERANCE = 300;

    private const EXPECTED_EVENT = 'otp.verified';

    /**
     * Test-only clock override. When set, called with no arguments to get
     * the current unix timestamp instead of time(). Reset to null when done.
     */
    public static ?\Closure $now = null;

    /**
     * Reports whether $signature matches
     * hex(HMAC-SHA256(secret, timestamp . "." . body)). The comparison is
     * constant-time. Performs no timestamp freshness check — use
     * parseVerifiedEvent() for full validation.
     */
    public static function verifySignature(string $secret, string $timestamp, string $body, string $signature): bool
    {
        $expected = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Validates an incoming otp.verified webhook and returns its payload.
     * Checks, in order: the signature (constant-time), then timestamp
     * freshness (anti-replay, ±$tolerance seconds in either direction), then
     * decodes the body. Pass the raw request body and the
     * X-OTPID-Timestamp / X-OTPID-Signature header values unmodified.
     *
     * @throws InvalidSignatureException when $signature does not match
     * @throws StaleTimestampException   when $timestamp is not a plain
     *                                    integer string, or is outside the
     *                                    tolerance window
     * @throws ApiException              when $body cannot be decoded as JSON
     * @throws UnexpectedEventException  when the decoded "event" is not
     *                                    "otp.verified"
     */
    public static function parseVerifiedEvent(
        string $secret,
        string $timestamp,
        string $signature,
        string $body,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): VerifiedEvent {
        if (!self::verifySignature($secret, $timestamp, $body, $signature)) {
            throw new InvalidSignatureException('otpid: invalid webhook signature');
        }

        $trimmed = trim($timestamp);
        if (!preg_match('/^-?[0-9]+$/', $trimmed)) {
            // Mirrors Go: strconv.ParseInt failure (non-numeric, including
            // floats like "1.5" or exponents like "1e5") is treated as a
            // stale timestamp, not a distinct error.
            throw new StaleTimestampException('otpid: webhook timestamp outside tolerance');
        }

        $ts = (int) $trimmed;
        $now = self::$now !== null ? (self::$now)() : time();
        if (abs($now - $ts) > $tolerance) {
            throw new StaleTimestampException('otpid: webhook timestamp outside tolerance');
        }

        $decoded = json_decode($body, true);
        if (!\is_array($decoded)) {
            throw new ApiException(ErrorCode::INVALID_RESPONSE, 'otpid: decode webhook payload', 0);
        }

        $event = Scalars::str($decoded, 'event');
        if ($event !== self::EXPECTED_EVENT) {
            throw new UnexpectedEventException(sprintf('otpid: unexpected webhook event: "%s"', $event));
        }

        return VerifiedEvent::fromArray($decoded);
    }
}
