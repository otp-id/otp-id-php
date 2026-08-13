<?php

declare(strict_types=1);

namespace OtpId\Transport;

/**
 * Performs a single HTTP request and returns the raw [status, body] pair.
 * Implementations must not retry and must not raise for non-2xx HTTP
 * status codes — envelope decoding and error mapping happen in Client.
 */
interface TransportInterface
{
    /**
     * @param array<string, string> $headers request headers, keyed by name
     *
     * @return array{int, string} [status, body]
     */
    public function request(string $method, string $url, array $headers, ?string $body): array;
}
