<?php

/**
 * Example: Email OTP — server-generated code delivered by email, then verify.
 *
 * Mirrors the docs cURL:
 *
 *   curl -X POST https://api.otp.id/v3/request \
 *     -d '{"channel": "email", "number": "user@example.com", "brand": "MyApp"}'
 *
 * Set OTPID_DESTINATION to the recipient email address for this example.
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
    fwrite(STDERR, "set OTPID_API_KEY and OTPID_DESTINATION (an email address) first\n");
    exit(1);
}

$client = new Client($apiKey);

try {
    $res = $client->requestOtp(new OrderParams(
        channel: Channel::Email,
        destination: $dest,
        brand: 'MyApp',
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

echo 'enter the code from the email: ';
$code = trim((string) fgets(STDIN));

try {
    $v = $client->verifyOtp($res->otpId, $code);
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
