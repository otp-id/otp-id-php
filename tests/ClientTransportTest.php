<?php

declare(strict_types=1);

namespace OtpId\Tests;

use OtpId\Client;
use OtpId\ErrorCode;
use OtpId\Exception\ApiException;
use OtpId\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

/**
 * Exercises Client's transport wiring and V3 envelope decoding via
 * FakeTransport — no real network I/O. Mirrors otp-id-go/client_test.go.
 */
final class ClientTransportTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    private function callDoRequest(Client $client, string $method, string $path, ?array $body = null, bool $expectData = true): array
    {
        $reflectionMethod = new \ReflectionMethod($client, 'doRequest');

        /** @var array<string, mixed> $result */
        $result = $reflectionMethod->invoke($client, $method, $path, $body, $expectData);

        return $result;
    }

    public function testConstructorTrimsApiKeyAndUsesDefaultBaseUrl(): void
    {
        $transport = new FakeTransport();
        $client = new Client("  test-key  \n", ['transport' => $transport]);

        $this->callDoRequest($client, 'GET', '/v3/account', null, false);

        self::assertSame('https://api.otp.id/v3/account', $transport->lastUrl);
        self::assertSame('Bearer test-key', $transport->lastHeaders['Authorization']);
    }

    public function testBaseUrlTrailingSlashIsTrimmed(): void
    {
        $transport = new FakeTransport();
        $client = new Client('test-key', [
            'base_url' => 'https://example.com/',
            'transport' => $transport,
        ]);

        $this->callDoRequest($client, 'GET', '/v3/account', null, false);

        self::assertSame('https://example.com/v3/account', $transport->lastUrl);
    }

    public function testDoRequestSendsHeaders(): void
    {
        $transport = new FakeTransport();
        $client = new Client('test-key', ['transport' => $transport]);

        $this->callDoRequest($client, 'POST', '/v3/request', ['a' => 'b']);

        self::assertSame('Bearer test-key', $transport->lastHeaders['Authorization']);
        self::assertSame('otp-id-php/' . Client::VERSION, $transport->lastHeaders['User-Agent']);
        self::assertSame('application/json', $transport->lastHeaders['Content-Type']);
        self::assertSame('{"a":"b"}', $transport->lastBody);
    }

    public function testDoRequestGetHasNoContentType(): void
    {
        $transport = new FakeTransport();
        $client = new Client('test-key', ['transport' => $transport]);

        $this->callDoRequest($client, 'GET', '/v3/account', null, false);

        self::assertArrayNotHasKey('Content-Type', $transport->lastHeaders);
        self::assertNull($transport->lastBody);
    }

    public function testDoRequestApiErrorMapping(): void
    {
        $transport = new FakeTransport(
            409,
            '{"success":false,"data":null,"error":{"code":"DUPLICATE_EXTERNAL_ID","message":"external_id already used","details":{"existing_otp_id":"OTP20260807ABCD000001"}}}'
        );
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $this->callDoRequest($client, 'POST', '/v3/request', ['a' => 'b']);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::DUPLICATE_EXTERNAL_ID, $exception->getErrorCode());
            self::assertSame(409, $exception->getHttpStatus());
            self::assertSame('OTP20260807ABCD000001', $exception->getDetails()['existing_otp_id'] ?? null);
        }
    }

    public function testDoRequestNonJsonResponseReturnsInvalidResponseWithSnippet(): void
    {
        $transport = new FakeTransport(502, '<html>502 Bad Gateway</html>');
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $this->callDoRequest($client, 'GET', '/v3/account', null, false);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::INVALID_RESPONSE, $exception->getErrorCode());
            self::assertSame(502, $exception->getHttpStatus());
            self::assertStringContainsString('502 Bad Gateway', $exception->getMessage());
        }
    }

    public function testDoRequestSuccessFalseWithoutErrorBodyIsInvalidResponse(): void
    {
        $transport = new FakeTransport(500, '{"success":false,"data":null,"error":null}');
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $this->callDoRequest($client, 'GET', '/v3/account', null, false);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::INVALID_RESPONSE, $exception->getErrorCode());
        }
    }

    public function testDoRequestApiErrorWithIncompleteErrorObjectYieldsEmptyCodeAndMessage(): void
    {
        // Mirrors Go: json.Unmarshal zero-values missing struct fields
        // instead of failing, so {"success":false,"error":{}} still yields
        // an APIError (with empty code/message), not INVALID_RESPONSE.
        $transport = new FakeTransport(500, '{"success":false,"data":null,"error":{}}');
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $this->callDoRequest($client, 'GET', '/v3/account', null, false);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame('', $exception->getErrorCode());
            self::assertNotSame(ErrorCode::INVALID_RESPONSE, $exception->getErrorCode());
            self::assertSame(500, $exception->getHttpStatus());
            self::assertNull($exception->getDetails());
        }
    }

    public function testDoRequestSuccessTrueWithNullDataAndExpectDataIsInvalidResponse(): void
    {
        $transport = new FakeTransport(200, '{"success":true,"data":null,"error":null}');
        $client = new Client('test-key', ['transport' => $transport]);

        try {
            $this->callDoRequest($client, 'GET', '/v3/account', null, true);
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(ErrorCode::INVALID_RESPONSE, $exception->getErrorCode());
        }
    }

    public function testDoRequestSuccessTrueWithNullDataWithoutExpectDataReturnsEmptyArray(): void
    {
        $transport = new FakeTransport(200, '{"success":true,"data":null,"error":null}');
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $this->callDoRequest($client, 'GET', '/v3/account', null, false);

        self::assertSame([], $result);
    }

    public function testDoRequestSuccessReturnsDecodedData(): void
    {
        $transport = new FakeTransport(200, '{"success":true,"data":{"otp_id":"OTP1"},"error":null}');
        $client = new Client('test-key', ['transport' => $transport]);

        $result = $this->callDoRequest($client, 'GET', '/v3/otp/OTP1', null, true);

        self::assertSame(['otp_id' => 'OTP1'], $result);
    }

    public function testEmptyApiKeyThrowsBeforeAnyTransportCall(): void
    {
        $transport = new FakeTransport();

        $this->expectException(\InvalidArgumentException::class);

        try {
            new Client('   ', ['transport' => $transport]);
        } finally {
            self::assertSame(0, $transport->callCount);
        }
    }
}
