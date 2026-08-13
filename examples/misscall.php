<?php

/**
 * Example: Missed Call OTP — the code is the last digits of the number
 * that calls the user, then verify.
 *
 * Mirrors the docs cURL:
 *
 *   curl -X POST https://api.otp.id/v3/request \
 *     -d '{"channel": "misscall", "number": "6281234567890"}'
 *
 * Notes: brand is not needed (no message body), and otp_length has no
 * effect — the code length is set by the telephony vendor. The response's
 * verification->prefix is the calling number MINUS the code digits, so the
 * UI can render "628559263-____" and ask the user to complete it from
 * their missed-call log.
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
        channel: Channel::Misscall,
        destination: $dest,
    ));
} catch (OtpIdException $e) {
    fatalApi($e);
}

printf("calling: otp_id=%s status=%s price=%d\n", $res->otpId, $res->status, $res->price);
if ($res->verification !== null) {
    printf(
        "the incoming call number starts with: %s (complete the last %d digits)\n",
        $res->verification->prefix,
        $res->verification->otpLength
    );
}

echo 'enter the LAST digits of the number that called: ';
$code = trim((string) fgets(STDIN));

try {
    $v = $client->verifyOtp($res->otpId, $code);
} catch (OtpIdException $e) {
    fatalApi($e);
}

if ($v->verified) {
    echo "verified!\n";
} else {
    echo "wrong digits: {$v->reason}\n";
}

function fatalApi(OtpIdException $e): never
{
    fwrite(STDERR, "error: {$e->getMessage()}\n");
    exit(1);
}
