<?php

declare(strict_types=1);

namespace OtpId;

use OtpId\Exception\ApiException;
use OtpId\Transport\CurlTransport;
use OtpId\Transport\TransportInterface;

/**
 * OTP.ID V3 API client. Every call performs a single HTTP request — the
 * SDK never retries automatically.
 */
final class Client
{
    /** Sent in the User-Agent header. */
    public const VERSION = '0.1.0';

    private const DEFAULT_BASE_URL = 'https://api.otp.id';

    private const DEFAULT_TIMEOUT = 30.0;

    /** First ~200 characters of a non-decodable body, for diagnostics. */
    private const SNIPPET_MAX_LENGTH = 200;

    private readonly string $apiKey;

    private readonly string $baseUrl;

    private readonly TransportInterface $transport;

    /**
     * @param array{base_url?: string, timeout?: float, transport?: TransportInterface} $options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('otpid: api key is empty');
        }

        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($options['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $this->transport = $options['transport'] ?? new CurlTransport($timeout);
    }

    /**
     * Creates an OTP transaction with a server-generated code
     * (POST /v3/request). The code itself is never returned.
     */
    public function requestOtp(OrderParams $params): OrderResult
    {
        return OrderResult::fromArray($this->doRequest('POST', '/v3/request', $params->toArray(), true));
    }

    /**
     * Delivers a client-generated code (POST /v3/send). The server rejects
     * Channel::Voice and Channel::WhatsAppInbound for this endpoint; use
     * Channel::WhatsApp, Channel::Sms, or Channel::Email.
     */
    public function sendOtp(string $otp, OrderParams $params): OrderResult
    {
        $body = $params->toArray();
        $body['otp'] = $otp;

        return OrderResult::fromArray($this->doRequest('POST', '/v3/send', $body, true));
    }

    /**
     * Checks a user-submitted code against a transaction (POST /v3/verify).
     * Do not call it for whatsapp_inbound transactions.
     */
    public function verifyOtp(string $otpId, string $otp): VerifyResult
    {
        $id = trim($otpId);
        if ($id === '') {
            throw new \InvalidArgumentException('otpid: otp_id is empty');
        }

        return VerifyResult::fromArray($this->doRequest('POST', '/v3/verify', ['otp_id' => $id, 'otp' => $otp], true));
    }

    /**
     * Fetches the current state of a transaction (GET /v3/otp/{otp_id}).
     */
    public function otpStatus(string $otpId): StatusResult
    {
        $id = trim($otpId);
        if ($id === '') {
            throw new \InvalidArgumentException('otpid: otp_id is empty');
        }

        return StatusResult::fromArray($this->doRequest('GET', '/v3/otp/' . rawurlencode($id), null, true));
    }

    /**
     * Fetches the merchant profile and credit balance for the API key in
     * use (GET /v3/account).
     */
    public function account(): AccountResult
    {
        return AccountResult::fromArray($this->doRequest('GET', '/v3/account', null, true));
    }

    /**
     * Creates a credit top-up invoice (POST /v3/topups). Call it from
     * server-side code only — never expose your API key to browsers or
     * mobile apps.
     *
     * $amount is the credit package in rupiah. The server accepts exactly:
     * 10000, 100000, 500000, 1000000, 2000000. $paymentMethodId selects
     * the payment method for the invoice.
     */
    public function createTopup(int $amount, int $paymentMethodId): TopupResult
    {
        return TopupResult::fromArray($this->doRequest('POST', '/v3/topups', [
            'amount' => $amount,
            'payment_method_id' => $paymentMethodId,
        ], true));
    }

    /**
     * Performs a single V3 API call and decodes the {success, data, error}
     * envelope. Every non-success outcome — including a response body the
     * SDK cannot make sense of — surfaces as an ApiException.
     *
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed> the decoded "data" payload
     */
    private function doRequest(string $method, string $path, ?array $body, bool $expectData): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'User-Agent' => 'otp-id-php/' . self::VERSION,
        ];

        $encodedBody = null;
        if ($body !== null) {
            $encodedBody = json_encode($body, JSON_THROW_ON_ERROR);
            $headers['Content-Type'] = 'application/json';
        }

        [$status, $raw] = $this->transport->request($method, $this->baseUrl . $path, $headers, $encodedBody);

        $invalid = fn (): ApiException => new ApiException(
            ErrorCode::INVALID_RESPONSE,
            self::bodySnippet($raw),
            $status
        );

        $decoded = json_decode($raw, true);
        if (!\is_array($decoded) || !\array_key_exists('success', $decoded) || !\is_bool($decoded['success'])) {
            throw $invalid();
        }

        if ($decoded['success'] === false) {
            $error = $decoded['error'] ?? null;
            if (!\is_array($error)) {
                // Mirrors Go: a missing/null "error" leaves env.Error nil,
                // which is the SDK's own INVALID_RESPONSE case.
                throw $invalid();
            }

            // Mirrors Go: json.Unmarshal zero-values any field missing from
            // the "error" object instead of failing, so an incomplete
            // object (e.g. {}) still yields an APIError with empty
            // code/message rather than collapsing to INVALID_RESPONSE.
            $code = \is_string($error['code'] ?? null) ? $error['code'] : '';
            $message = \is_string($error['message'] ?? null) ? $error['message'] : '';

            $details = $error['details'] ?? null;
            /** @var array<string, mixed>|null $details */
            $details = \is_array($details) ? $details : null;

            throw new ApiException($code, $message, $status, $details);
        }

        $data = $decoded['data'] ?? null;
        if (!$expectData) {
            return \is_array($data) ? $data : [];
        }

        if ($data === null || !\is_array($data)) {
            throw $invalid();
        }

        return $data;
    }

    /**
     * First ~200 characters of a raw body for diagnostics, without
     * splitting multi-byte characters.
     */
    private static function bodySnippet(string $raw): string
    {
        $trimmed = trim($raw);
        if (mb_strlen($trimmed, 'UTF-8') > self::SNIPPET_MAX_LENGTH) {
            return mb_substr($trimmed, 0, self::SNIPPET_MAX_LENGTH, 'UTF-8');
        }

        return $trimmed;
    }
}
