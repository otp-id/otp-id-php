<?php

declare(strict_types=1);

namespace OtpId;

/**
 * OTP delivery channel accepted by the V3 API.
 */
final class Channel
{
    public const WHATSAPP = 'whatsapp';
    public const SMS = 'sms';
    public const VOICE = 'voice';
    public const EMAIL = 'email';
    public const MISSCALL = 'misscall';
    public const WHATSAPP_INBOUND = 'whatsapp_inbound';

    private function __construct()
    {
        // Static constant holder — not instantiable.
    }
}
