<?php

declare(strict_types=1);

namespace OtpId;

/**
 * OTP delivery channel accepted by the V3 API.
 */
enum Channel: string
{
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case Voice = 'voice';
    case Email = 'email';
    case Misscall = 'misscall';
    case WhatsAppInbound = 'whatsapp_inbound';
}
