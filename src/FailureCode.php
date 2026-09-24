<?php

declare(strict_types=1);

namespace OtpId;

/**
 * Public delivery-failure reason codes the OTP.ID V3 API classifies a
 * failed transaction into (`DeliveryFailure::$code`), kept in sync with the
 * server. Distinct from ErrorCode: these describe why an otherwise
 * successfully-accepted transaction failed to deliver, not why the API call
 * itself was rejected. Treat unknown codes as forward-compatible — new ones
 * may be added without a major version bump.
 */
final class FailureCode
{
    public const NUMBER_NOT_ON_WHATSAPP = 'NUMBER_NOT_ON_WHATSAPP';
    public const TOO_FREQUENT = 'TOO_FREQUENT';
    public const CHANNEL_UNAVAILABLE = 'CHANNEL_UNAVAILABLE';
    public const PROVIDER_UNAVAILABLE = 'PROVIDER_UNAVAILABLE';
    public const DELIVERY_FAILED = 'DELIVERY_FAILED';

    private function __construct()
    {
        // Static constant holder — not instantiable.
    }
}
