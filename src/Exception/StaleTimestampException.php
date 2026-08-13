<?php

declare(strict_types=1);

namespace OtpId\Exception;

/**
 * Thrown by Webhook::parseVerifiedEvent() when the timestamp is outside
 * the allowed tolerance window (anti-replay) or is not numeric.
 */
class StaleTimestampException extends OtpIdException
{
}
