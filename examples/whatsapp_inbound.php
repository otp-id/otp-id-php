<?php

/**
 * Example: WhatsApp Inbound — the USER sends a WhatsApp message to OTP.ID
 * instead of typing a code. There is nothing to verify manually: OTP.ID
 * matches the incoming message to the transaction automatically. This
 * example polls GET /v3/otp/{otp_id} until the status becomes "verified".
 *
 * Mirrors the docs cURL:
 *
 *   curl -X POST https://api.otp.id/v3/request \
 *     -d '{"channel": "whatsapp_inbound", "brand": "MyApp", "ttl": 300}'
 *
 * Do NOT call verifyOtp() for this channel — inbound transactions carry no
 * code, so any submission counts as a failed attempt. In production,
 * prefer the otp.verified webhook over polling (see the root README).
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use OtpId\Channel;
use OtpId\Client;
use OtpId\Exception\OtpIdException;
use OtpId\OrderParams;

const POLL_INTERVAL_SECONDS = 3;
const POLL_TIMEOUT_SECONDS = 5 * 60;

$apiKey = getenv('OTPID_API_KEY');
if ($apiKey === false || $apiKey === '') {
    fwrite(STDERR, "set OTPID_API_KEY first\n");
    exit(1);
}

$client = new Client($apiKey);

try {
    $res = $client->requestOtp(new OrderParams(
        channel: Channel::WhatsAppInbound,
        brand: 'MyApp',
        ttl: 300,
    ));
} catch (OtpIdException $e) {
    fatalApi($e);
}

if ($res->verification === null) {
    fwrite(STDERR, "expected a verification block for whatsapp_inbound\n");
    exit(1);
}

printf("created: otp_id=%s status=%s\n", $res->otpId, $res->status);
echo "ask the user to tap this link and send the pre-filled message:\n";
echo '  ' . $res->verification->waLink . "\n";
printf(
    "(or message \"%s\" to %s — valid until %s)\n",
    $res->verification->message,
    $res->verification->waNumber,
    $res->verification->expiresAt
);

echo "waiting for the user's WhatsApp message...\n";
$deadline = time() + POLL_TIMEOUT_SECONDS;
while (time() < $deadline) {
    sleep(POLL_INTERVAL_SECONDS);

    try {
        $status = $client->otpStatus($res->otpId);
    } catch (OtpIdException $e) {
        fatalApi($e);
    }

    if ($status->status === 'verified') {
        echo "verified at {$status->verifiedAt}\n";
        exit(0);
    }
}

echo "timed out — the user never sent the message\n";

function fatalApi(OtpIdException $e): never
{
    fwrite(STDERR, "error: {$e->getMessage()}\n");
    exit(1);
}
