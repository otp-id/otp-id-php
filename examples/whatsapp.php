<?php

/**
 * Example: WhatsApp OTP — server-generated code, then verify.
 *
 * Mirrors the docs cURL:
 *
 *   curl -X POST https://api.otp.id/v3/request \
 *     -d '{"channel": "whatsapp", "number": "6281234567890", "brand": "MyApp", "ttl": 300}'
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use OtpId\Channel;
use OtpId\Client;
use OtpId\Exception\OtpIdException;
use OtpId\OrderParams;

$apiKey = getenv('OTPID_API_KEY');
$dest = getenv('OTPID_DESTINATION');
if ($apiKey === false || $apiKey === '' || $dest === false || $dest === '') {
    fwrite(STDERR, "set OTPID_API_KEY and OTPID_DESTINATION first\n");
    exit(1);
}

$client = new Client($apiKey);

try {
    $res = $client->requestOtp(new OrderParams(
        channel: Channel::WhatsApp,
        destination: $dest,
        brand: 'MyApp',
        ttl: 300,
    ));
} catch (OtpIdException $e) {
    fatalApi($e);
}

printf(
    "sent: otp_id=%s status=%s price=%d last_balance=%d\n",
    $res->otpId,
    $res->status,
    $res->price,
    $res->lastBalance
);

echo 'enter the code the user received on WhatsApp: ';
$code = trim((string) fgets(STDIN));

try {
    $v = $client->verifyOtp($res->otpId, $code);
} catch (OtpIdException $e) {
    fatalApi($e);
}

if ($v->verified) {
    echo "verified!\n";
} else {
    echo "wrong code: {$v->reason}\n"; // "mismatch" — not an error
}

function fatalApi(OtpIdException $e): never
{
    fwrite(STDERR, "error: {$e->getMessage()}\n");
    exit(1);
}
