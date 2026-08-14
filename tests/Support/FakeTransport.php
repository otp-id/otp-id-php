<?php

declare(strict_types=1);

namespace OtpId\Tests\Support;

use OtpId\Transport\TransportInterface;

/**
 * Records the last request made through it and returns a canned response.
 * Lets Client tests exercise doRequest() without any real network I/O.
 */
final class FakeTransport implements TransportInterface
{
    public ?string $lastMethod = null;

    public ?string $lastUrl = null;

    /** @var array<string, string> */
    public array $lastHeaders = [];

    public ?string $lastBody = null;

    public int $callCount = 0;

    private int $responseStatus;

    private string $responseBody;

    public function __construct(int $responseStatus = 200, string $responseBody = '{"success":true,"data":{},"error":null}')
    {
        $this->responseStatus = $responseStatus;
        $this->responseBody = $responseBody;
    }

    public function request(string $method, string $url, array $headers, ?string $body): array
    {
        ++$this->callCount;
        $this->lastMethod = $method;
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;
        $this->lastBody = $body;

        return [$this->responseStatus, $this->responseBody];
    }
}
