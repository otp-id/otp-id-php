<?php

declare(strict_types=1);

namespace OtpId\Exception;

/**
 * Thrown by Webhook::parseVerifiedEvent() when the decoded payload's
 * "event" field is not "otp.verified".
 */
class UnexpectedEventException extends OtpIdException
{
}
