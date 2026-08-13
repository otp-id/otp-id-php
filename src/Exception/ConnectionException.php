<?php

declare(strict_types=1);

namespace OtpId\Exception;

/**
 * Thrown by CurlTransport when a request fails at the network layer
 * (DNS failure, connection refused, TLS error, timeout, and so on) —
 * i.e. no HTTP response was received at all.
 */
class ConnectionException extends OtpIdException
{
}
