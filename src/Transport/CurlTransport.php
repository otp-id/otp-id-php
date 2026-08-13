<?php

declare(strict_types=1);

namespace OtpId\Transport;

use OtpId\Exception\ConnectionException;

/**
 * Default TransportInterface implementation, backed by ext-curl.
 */
final class CurlTransport implements TransportInterface
{
    /** Guard against abnormal responses — matches the Go SDK's maxBodyBytes. */
    private const MAX_BODY_BYTES = 1_048_576;

    public function __construct(private readonly float $timeout = 30.0)
    {
    }

    public function request(string $method, string $url, array $headers, ?string $body): array
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new ConnectionException('otpid: failed to initialize curl handle');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $buffer = '';
        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => (int) $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_WRITEFUNCTION => static function (mixed $ch, string $data) use (&$buffer): int {
                $length = \strlen($data);
                $remaining = self::MAX_BODY_BYTES - \strlen($buffer);
                if ($remaining > 0) {
                    $buffer .= $remaining >= $length ? $data : substr($data, 0, $remaining);
                }

                // Always report the full length consumed so curl does not
                // abort the transfer with CURLE_WRITE_ERROR — excess bytes
                // beyond the cap are simply discarded, mirroring the Go
                // SDK's io.LimitReader truncation (not a network error).
                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        curl_exec($handle);

        if (curl_errno($handle) !== 0) {
            $message = curl_error($handle);
            curl_close($handle);

            throw new ConnectionException(sprintf('otpid: %s %s: %s', $method, $url, $message));
        }

        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return [$status, $buffer];
    }
}
