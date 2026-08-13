<?php

declare(strict_types=1);

namespace OtpId\Exception;

/**
 * Thrown by Webhook::parseVerifiedEvent() when the signature does not
 * match the expected HMAC for the given secret, timestamp, and body.
 */
class InvalidSignatureException extends OtpIdException
{
}
