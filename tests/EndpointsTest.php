<?php

declare(strict_types=1);

namespace OtpId\Tests;

use OtpId\Channel;
use OtpId\Client;
use OtpId\ErrorCode;
use OtpId\Exception\ApiException;
use OtpId\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the six V3 endpoint methods (requestOtp, sendOtp, verifyOtp,
 * otpStatus, account, createTopup) via FakeTransport — no real network I/O.
 * Fixtures are copied verbatim from otp-id-go's order_test.go, verify_test.go,
 * status_test.go, account_test.go and topup_test.go so both SDKs are proven
 * against the same wire contract.
 */
final class EndpointsTest extends TestCase
{
    private const ORDER_WHATSAPP_FIXTURE = '{"success":true,"data":{"otp_id":"OTP20260807ABCD000001","status":"sent","channel":"whatsapp","number":"6281234567890","price":350,"last_balance":99650,"expires_at":"2026-08-07 10:05:00"},"error":null}';

    private const ORDER_INBOUND_FIXTURE = '{"success":true,"data":{"otp_id":"OTP20260807ABCD000002","status":"pending","channel":"whatsapp_inbound","number":"","price":350,"last_balance":99300,"expires_at":"2026-08-07 10:05:00","verification":{"wa_number":"6285212345678","message":"OTPID V-8FK2QN9P — verifikasi MyApp. Kirim pesan ini tanpa mengubah isinya.","wa_link":"https://wa.me/6285212345678?text=OTPID%20V-8FK2QN9P","expires_at":"2026-08-07 10:05:00"}},"error":null}';

    private const ORDER_MISSCALL_FIXTURE = '{"success":true,"data":{"otp_id":"OTP20260807ABCD000003","status":"sent","channel":"misscall","number":"6281234567890","price":250,"last_balance":99050,"expires_at":"2026-08-07 10:05:00","verification":{"prefix":"628559263","otp_length":4}},"error":null}';

    /**
     * @return array<string, mixed>
     */
    private static function decodedBody(FakeTransport $transport): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($transport->lastBody ?? '', true);

        return $decoded;
    }

    private static function path(FakeTransport $transport): string
    {
        return (string) parse_url($transport->lastUrl ?? '', PHP_URL_PATH);
    }

    // --- requestOtp / sendOtp (order.go) ------------------------------

    public function testRequestOtpWhatsAppSuccess(): void
    {
        $transport = new FakeTransport(200, self::ORDER_WHATSAPP_FIXTURE);
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->requestOtp([
            'channel' => Channel::WHATSAPP,
            'destination' => '6281234567890',
            'brand' => 'MyApp',
            'otp_length' => 6,
            'ttl' => 300,
            'external_id' => 'order-8821',
        ]);

        self::assertSame('/v3/request', self::path($transport));

        $sentBody = self::decodedBody($transport);
        self::assertSame('whatsapp', $sentBody['channel']);
        self::assertSame('6281234567890', $sentBody['destination']);
        self::assertSame('MyApp', $sentBody['brand']);
        self::assertSame('order-8821', $sentBody['external_id']);
        self::assertSame(6, $sentBody['otp_length']);
        self::assertSame(300, $sentBody['ttl']);
        self::assertArrayNotHasKey('otp', $sentBody);

        self::assertSame('OTP20260807ABCD000001', $result->otpId);
        self::assertSame('sent', $result->status);
        self::assertSame('whatsapp', $result->channel);
        self::assertSame(350, $result->price);
        self::assertSame(99650, $result->lastBalance);
        self::assertSame('2026-08-07 10:05:00', $result->expiresAt);
        self::assertNull($result->verification);
    }

    public function testRequestOtpOmitsEmptyOptionalFields(): void
    {
        $transport = new FakeTransport(200, self::ORDER_INBOUND_FIXTURE);
        $client = new Client('test-key', ['transport' => $transport]);

        $client->requestOtp(['channel' => Channel::WHATSAPP_INBOUND]);

        $sentBody = self::decodedBody($transport);
        foreach (['destination', 'brand', 'otp_length', 'ttl', 'external_id'] as $key) {
            self::assertArrayNotHasKey($key, $sentBody, "optional field \"$key\" must be omitted when empty");
        }
    }

    public function testRequestOtpInboundVerification(): void
    {
        $transport = new FakeTransport(200, self::ORDER_INBOUND_FIXTURE);
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->requestOtp(['channel' => Channel::WHATSAPP_INBOUND]);

        self::assertNotNull($result->verification);
        self::assertSame('6285212345678', $result->verification->waNumber);
        self::assertNotSame('', $result->verification->waLink);
        self::assertNotSame('', $result->verification->message);
        self::assertSame('2026-08-07 10:05:00', $result->verification->expiresAt);
    }

    public function testRequestOtpMisscallVerification(): void
    {
        $transport = new FakeTransport(200, self::ORDER_MISSCALL_FIXTURE);
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->requestOtp(['channel' => Channel::MISSCALL, 'destination' => '6281234567890']);

        self::assertNotNull($result->verification);
        self::assertSame('628559263', $result->verification->prefix);
        self::assertSame(4, $result->verification->otpLength);
    }

    public function testSendOtpBodyIncludesOtpAndReturnsResult(): void
    {
        $transport = new FakeTransport(200, self::ORDER_WHATSAPP_FIXTURE);
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->sendOtp('482913', [
            'channel' => Channel::WHATSAPP,
            'destination' => '6281234567890',
        ]);

        self::assertSame('/v3/send', self::path($transport));

        $sentBody = self::decodedBody($transport);
        self::assertSame('482913', $sentBody['otp']);
        self::assertSame('whatsapp', $sentBody['channel']);
        self::assertSame('6281234567890', $sentBody['destination']);

        self::assertSame('OTP20260807ABCD000001', $result->otpId);
        self::assertSame('sent', $result->status);
        self::assertSame(99650, $result->lastBalance);
    }

    public function testRequestOtpInsufficientBalanceIsApiException(): void
    {
        $transport = new FakeTransport(
            402,
            '{"success":false,"data":null,"error":{"code":"INSUFFICIENT_BALANCE","message":"balance is not enough"}}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $client->requestOtp(['channel' => Channel::SMS, 'destination' => '6281234567890']);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::INSUFFICIENT_BALANCE, $exception->getErrorCode());
        }
    }

    // --- verifyOtp (verify.go) -----------------------------------------

    public function testVerifyOtpSuccess(): void
    {
        $transport = new FakeTransport(
            200,
            '{"success":true,"data":{"otp_id":"OTP20260807ABCD000001","verified":true,"reason":""},"error":null}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->verifyOtp('OTP20260807ABCD000001', '482913');

        self::assertSame('/v3/verify', self::path($transport));

        $sentBody = self::decodedBody($transport);
        self::assertSame('OTP20260807ABCD000001', $sentBody['otp_id']);
        self::assertSame('482913', $sentBody['otp']);

        self::assertTrue($result->verified);
        self::assertSame('', $result->reason);
    }

    public function testVerifyOtpMismatchIsNotAnException(): void
    {
        $transport = new FakeTransport(
            200,
            '{"success":true,"data":{"otp_id":"OTP20260807ABCD000001","verified":false,"reason":"mismatch"},"error":null}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->verifyOtp('OTP20260807ABCD000001', '000000');

        self::assertFalse($result->verified);
        self::assertSame('mismatch', $result->reason);
    }

    public function testVerifyOtpExpiredIsApiException(): void
    {
        $transport = new FakeTransport(
            422,
            '{"success":false,"data":null,"error":{"code":"OTP_EXPIRED","message":"otp has expired"}}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $client->verifyOtp('OTP20260807ABCD000001', '482913');
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::OTP_EXPIRED, $exception->getErrorCode());
            self::assertSame(422, $exception->getHttpStatus());
        }
    }

    public function testVerifyOtpEmptyOtpIdThrowsWithoutNetworkCall(): void
    {
        $transport = new FakeTransport();
        $client = new Client('test-key', ['transport' => $transport]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $client->verifyOtp('   ', '482913');
        } finally {
            self::assertSame(0, $transport->callCount);
        }
    }

    // --- otpStatus (status.go) ------------------------------------------

    public function testOtpStatusSuccess(): void
    {
        $transport = new FakeTransport(
            200,
            '{"success":true,"data":{"otp_id":"OTP20260807ABCD000001","status":"sent","channel":"whatsapp","number":"6281234567890","attempts":0,"expires_at":"2026-08-07 10:05:00","verified_at":"","price":350},"error":null}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->otpStatus('OTP20260807ABCD000001');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('/v3/otp/OTP20260807ABCD000001', self::path($transport));
        self::assertSame('sent', $result->status);
        self::assertSame(0, $result->attempts);
        self::assertSame('', $result->verifiedAt);
        self::assertSame(350, $result->price);
        self::assertNull($result->verification);
    }

    public function testOtpStatusMisscallPrefix(): void
    {
        $transport = new FakeTransport(
            200,
            '{"success":true,"data":{"otp_id":"OTP20260807ABCD000003","status":"sent","channel":"misscall","number":"6281234567890","attempts":1,"expires_at":"2026-08-07 10:05:00","verified_at":"","price":250,"verification":{"prefix":"628559263"}},"error":null}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->otpStatus('OTP20260807ABCD000003');

        self::assertNotNull($result->verification);
        self::assertSame('628559263', $result->verification->prefix);
    }

    public function testOtpStatusPathEscapesOtpId(): void
    {
        $transport = new FakeTransport(
            404,
            '{"success":false,"data":null,"error":{"code":"OTP_NOT_FOUND","message":"not found"}}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $client->otpStatus('weird/../id');
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::OTP_NOT_FOUND, $exception->getErrorCode());
        }

        self::assertStringContainsString('weird%2F..%2Fid', $transport->lastUrl ?? '');
    }

    public function testOtpStatusEmptyOtpIdThrowsWithoutNetworkCall(): void
    {
        $transport = new FakeTransport();
        $client = new Client('test-key', ['transport' => $transport]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $client->otpStatus('');
        } finally {
            self::assertSame(0, $transport->callCount);
        }
    }

    // --- account (account.go) -------------------------------------------

    public function testAccountSuccess(): void
    {
        $transport = new FakeTransport(
            200,
            '{"success":true,"data":{"merchant_id":"M123","name":"PT Contoh","brand_name":"MyApp","brand_email":"otp@myapp.co.id","email":"owner@myapp.co.id","saldo":99650},"error":null}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->account();

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('/v3/account', self::path($transport));
        self::assertSame('M123', $result->merchantId);
        self::assertSame('MyApp', $result->brandName);
        self::assertSame(99650, $result->saldo);
    }

    public function testAccountUnauthorizedIsApiException(): void
    {
        $transport = new FakeTransport(
            401,
            '{"success":false,"data":null,"error":{"code":"UNAUTHORIZED","message":"invalid api key"}}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $client->account();
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::UNAUTHORIZED, $exception->getErrorCode());
            self::assertSame(401, $exception->getHttpStatus());
        }
    }

    // --- createTopup (topup.go) ------------------------------------------

    public function testCreateTopupSuccess(): void
    {
        $transport = new FakeTransport(
            200,
            '{"success":true,"data":{"topup_id":"TC20990809Q7M4X2A8BC5D6EFG","payment_url":"https://app.otp.id/topup/TC20990809Q7M4X2A8BC5D6EFG?hash=abc","payment_hash":"abc","amount":100000,"payment_total":100750,"payment_method_id":3,"payment_method":"QRIS","payment_type":"qris","payment_expired_at":"2026-08-14 12:00:00","status":"pending"},"error":null}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $client->createTopup(100000, 3);

        self::assertSame('/v3/topups', self::path($transport));

        $sentBody = self::decodedBody($transport);
        self::assertSame(100000, $sentBody['amount']);
        self::assertSame(3, $sentBody['payment_method_id']);

        self::assertSame('TC20990809Q7M4X2A8BC5D6EFG', $result->topupId);
        self::assertSame(100750, $result->paymentTotal);
        self::assertSame('QRIS', $result->paymentMethod);
    }

    public function testCreateTopupValidationErrorIsApiException(): void
    {
        $transport = new FakeTransport(
            400,
            '{"success":false,"data":null,"error":{"code":"VALIDATION_ERROR","message":"amount must be one of 10000, 100000, 500000, 1000000, 2000000"}}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $client->createTopup(12345, 3);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::VALIDATION_ERROR, $exception->getErrorCode());
        }
    }
}
