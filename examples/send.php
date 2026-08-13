<?php

/**
 * Example: sendOtp() — you generate the code yourself and OTP.ID only
 * delivers it (POST /v3/send). Supported delivery channels: whatsapp,
 * sms, email.
 *
 * Mirrors the docs cURL:
 *
 *   curl -X POST https://api.otp.id/v3/send \
 *     -d '{"channel": "sms", "number": "6281234567890", "otp": "482913",
 *          "brand": "MyApp", "ttl": 180, "external_id": "order-8822"}'
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

// Generate our own 6-digit code — with sendOtp(), code generation and
// storage are the caller's responsibility; OTP.ID only delivers it.
$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$client = new Client($apiKey);

try {
    $res = $client->sendOtp($code, new OrderParams(
        channel: Channel::Sms,
        destination: $dest,
        brand: 'MyApp',
        ttl: 180,
        externalId: 'order-8822',
    ));
} catch (OtpIdException $e) {
    fatalApi($e);
}

printf("sent our own code: otp_id=%s status=%s price=%d\n", $res->otpId, $res->status, $res->price);

echo 'enter the code the user received: ';
$entered = trim((string) fgets(STDIN));

// Verification still goes through OTP.ID — it stored a hash of the code it
// delivered, so verifyOtp() works exactly like with requestOtp().
try {
    $v = $client->verifyOtp($res->otpId, $entered);
} catch (OtpIdException $e) {
    fatalApi($e);
}

if ($v->verified) {
    echo "verified!\n";
} else {
    echo "wrong code: {$v->reason}\n";
}

function fatalApi(OtpIdException $e): never
{
    fwrite(STDERR, "error: {$e->getMessage()}\n");
    exit(1);
}
