<?php

declare(strict_types=1);

namespace OtpId\Tests;

use OtpId\Exception\ApiException;
use OtpId\Exception\InvalidSignatureException;
use OtpId\Exception\StaleTimestampException;
use OtpId\Exception\UnexpectedEventException;
use OtpId\Webhook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exercises Webhook::verifySignature() / Webhook::parseVerifiedEvent().
 * The known-good vector and every fixture body below are copied verbatim
 * from otp-id-go's webhook_test.go so both SDKs are proven against the
 * same signing scheme.
 */
final class WebhookTest extends TestCase
{
    // HMAC-SHA256("whsec_testsecret", "1765700000" + "." + self::BODY)
    private const SECRET = 'whsec_testsecret';

    private const TIMESTAMP = '1765700000';

    private const SIGNATURE = '41c831b6192fa304f0564bd03bb147587119a97a579bb7a0fc12ae9b2c8ed4ca';

    private const BODY = '{"event":"otp.verified","otp_id":"OTP20260807ABCD000001","external_id":"order-8821","channel":"whatsapp","number":"6281234567890","verified_at":"2026-08-07 10:01:30"}';

    protected function tearDown(): void
    {
        Webhook::$now = null;
    }

    // --- verifySignature() ---------------------------------------------

    public function testVerifySignatureVector(): void
    {
        self::assertTrue(Webhook::verifySignature(self::SECRET, self::TIMESTAMP, self::BODY, self::SIGNATURE));
    }

    public function testVerifySignatureMatchesReferenceHmac(): void
    {
        // Cross-check against an inline reference implementation so the SDK
        // cannot silently drift from the server's signing scheme.
        $reference = hash_hmac('sha256', self::TIMESTAMP . '.' . self::BODY, self::SECRET);

        self::assertTrue(Webhook::verifySignature(self::SECRET, self::TIMESTAMP, self::BODY, $reference));
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function rejectionCases(): iterable
    {
        yield 'wrong secret' => ['other-secret', self::TIMESTAMP, self::BODY, self::SIGNATURE];
        yield 'wrong timestamp' => [self::SECRET, '1765700001', self::BODY, self::SIGNATURE];
        yield 'tampered body' => [self::SECRET, self::TIMESTAMP, '{"event":"otp.verified","otp_id":"HACKED"}', self::SIGNATURE];
        yield 'wrong signature' => [self::SECRET, self::TIMESTAMP, self::BODY, 'deadbeef'];
    }

    #[DataProvider('rejectionCases')]
    public function testVerifySignatureRejects(string $secret, string $timestamp, string $body, string $signature): void
    {
        self::assertFalse(Webhook::verifySignature($secret, $timestamp, $body, $signature));
    }

    // --- parseVerifiedEvent() -------------------------------------------

    public function testParseVerifiedEventSuccess(): void
    {
        Webhook::$now = fn (): int => 1765700000 + 30;

        $event = Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, self::SIGNATURE, self::BODY);

        self::assertSame('otp.verified', $event->event);
        self::assertSame('OTP20260807ABCD000001', $event->otpId);
        self::assertSame('order-8821', $event->externalId);
        self::assertSame('whatsapp', $event->channel);
        self::assertSame('6281234567890', $event->number);
        self::assertSame('2026-08-07 10:01:30', $event->verifiedAt);
    }

    public function testParseVerifiedEventInvalidSignature(): void
    {
        Webhook::$now = fn (): int => 1765700000;

        $this->expectException(InvalidSignatureException::class);
        Webhook::parseVerifiedEvent('other-secret', self::TIMESTAMP, self::SIGNATURE, self::BODY);
    }

    public function testParseVerifiedEventStaleTimestampPast(): void
    {
        // 6 minutes after the signed timestamp: outside the 5-minute default tolerance.
        Webhook::$now = fn (): int => 1765700000 + 6 * 60;

        $this->expectException(StaleTimestampException::class);
        Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, self::SIGNATURE, self::BODY);
    }

    public function testParseVerifiedEventStaleTimestampFuture(): void
    {
        // Clock skew guard also applies in the other direction.
        Webhook::$now = fn (): int => 1765700000 - 6 * 60;

        $this->expectException(StaleTimestampException::class);
        Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, self::SIGNATURE, self::BODY);
    }

    public function testParseVerifiedEventNonNumericTimestamp(): void
    {
        Webhook::$now = fn (): int => 1765700000;

        $timestamp = 'not-a-number';
        $signature = hash_hmac('sha256', $timestamp . '.' . self::BODY, self::SECRET);

        $this->expectException(StaleTimestampException::class);
        Webhook::parseVerifiedEvent(self::SECRET, $timestamp, $signature, self::BODY);
    }

    public function testParseVerifiedEventFloatStringTimestamp(): void
    {
        // is_numeric("1.5") is true in PHP but this must still be rejected —
        // Go's strconv.ParseInt (base 10, no fractional part) would fail too.
        Webhook::$now = fn (): int => 1765700000;

        $timestamp = '1.5';
        $signature = hash_hmac('sha256', $timestamp . '.' . self::BODY, self::SECRET);

        $this->expectException(StaleTimestampException::class);
        Webhook::parseVerifiedEvent(self::SECRET, $timestamp, $signature, self::BODY);
    }

    public function testParseVerifiedEventBadJson(): void
    {
        Webhook::$now = fn (): int => 1765700000;

        $body = '{not-json';
        $signature = hash_hmac('sha256', self::TIMESTAMP . '.' . $body, self::SECRET);

        try {
            Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, $signature, $body);
            self::fail('expected an exception');
        } catch (InvalidSignatureException|StaleTimestampException|UnexpectedEventException $e) {
            self::fail('expected an ApiException, got ' . $e::class);
        } catch (ApiException $e) {
            self::assertSame('INVALID_RESPONSE', $e->getErrorCode());
            self::assertSame(0, $e->getHttpStatus());
        }
    }

    public function testParseVerifiedEventUnexpectedEvent(): void
    {
        Webhook::$now = fn (): int => 1765700000;

        $body = '{"event":"otp.expired","otp_id":"OTP20260807ABCD000001"}';
        $signature = hash_hmac('sha256', self::TIMESTAMP . '.' . $body, self::SECRET);

        $this->expectException(UnexpectedEventException::class);
        $this->expectExceptionMessage('otp.expired');
        Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, $signature, $body);
    }

    public function testParseVerifiedEventCustomTolerance(): void
    {
        // 90 seconds after the signed timestamp: within the default 300s
        // tolerance but outside a custom 60s tolerance.
        Webhook::$now = fn (): int => 1765700000 + 90;

        $this->expectException(StaleTimestampException::class);
        Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, self::SIGNATURE, self::BODY, 60);
    }

    public function testParseVerifiedEventCustomToleranceAccepts(): void
    {
        // Same 90s offset, but within a wider custom tolerance.
        Webhook::$now = fn (): int => 1765700000 + 90;

        $event = Webhook::parseVerifiedEvent(self::SECRET, self::TIMESTAMP, self::SIGNATURE, self::BODY, 120);

        self::assertSame('otp.verified', $event->event);
    }

    public function testDefaultToleranceConstant(): void
    {
        self::assertSame(300, Webhook::DEFAULT_TOLERANCE);
    }
}
