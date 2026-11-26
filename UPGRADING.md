# Upgrading

## From v0.2.x to v0.3.0

```bash
composer require otp-id/otp-id-php:^0.3
```

**Not breaking.** All changes are additive: new classes (`DeliveryFailure`,
`FailureCode`), a new nullable `$failure` property on `OrderResult` and
`StatusResult`, and a new optional trailing `$failure` constructor argument
on both. Existing calls to `requestOtp()`, `sendOtp()`, `verifyOtp()`,
`otpStatus()`, `account()` and `createTopup()` work unchanged.

The billing change for `whatsapp_inbound` (below) happens on the server and
applies to every SDK version, but you may need to adjust your own logic if
you assumed the balance drops at request time.

### Delivery failure reasons

When `$status === 'failed'`, `requestOtp()`, `sendOtp()` and `otpStatus()`
now tell you why, via `$result->failure` (a `DeliveryFailure` with `code` and
an Indonesian `message`; never the raw vendor error). It is `null` for every
other status.

```php
use OtpId\Channel;
use OtpId\FailureCode;

$res = $client->requestOtp(['channel' => Channel::WHATSAPP, 'destination' => '6281234567890']);

if ($res->status === 'failed' && $res->failure !== null) {
    switch ($res->failure->code) {
        case FailureCode::NUMBER_NOT_ON_WHATSAPP:
            // offer SMS instead
            break;
        case FailureCode::TOO_FREQUENT:
            // ask the user to wait before retrying
            break;
        default:
            // CHANNEL_UNAVAILABLE, PROVIDER_UNAVAILABLE, DELIVERY_FAILED,
            // or a code added later: treat as a generic failure
            break;
    }
    echo $res->failure->message; // safe to show
}
```

### `otpStatus()` returns `verification` for pending WhatsApp Inbound

`StatusResult::$verification` used to be set only for misscall. It is now
also set for `whatsapp_inbound` transactions that are `pending` and not yet
expired, so you can recover the wa.me link while polling (for example after
a page reload) instead of storing it yourself.

```php
$st = $client->otpStatus($otpId);
if ($st->status === 'pending' && $st->verification !== null) {
    echo "Tap to verify: {$st->verification->waLink}\n";
}
```

### WhatsApp Inbound: pay only when verified

`whatsapp_inbound` transactions are now charged only when they become
`verified`:

- `requestOtp()` returns `status: "pending"`; `$res->price` is the amount
  that will be charged on verification and `$res->lastBalance` is unchanged.
- The transaction stays `pending` while waiting for the user's WhatsApp
  message. A message from a different number than the destination is
  rejected and the transaction stays `pending`.
- If the TTL passes without a match it ends as `expired` and is not charged.

Poll until a terminal status (or use the `otp.verified` webhook):

```php
$res = $client->requestOtp(['channel' => Channel::WHATSAPP_INBOUND, 'destination' => '6281234567890']);
echo "Ask the user to tap: {$res->verification->waLink}\n";

$deadline = time() + 300;
do {
    sleep(3);
    $st = $client->otpStatus($res->otpId);
} while (!in_array($st->status, ['verified', 'expired', 'failed'], true) && time() < $deadline);

if ($st->status === 'verified') {
    // charged $st->price; log the user in
} elseif ($st->status === 'expired') {
    // not charged; offer to start again
} elseif ($st->status === 'failed') {
    echo $st->failure !== null ? $st->failure->message : 'failed';
}
```

## From v0.1.x to v0.2.0

```bash
composer require otp-id/otp-id-php:^0.2
```

**Breaking.** v0.2.0 lowered the PHP floor from 8.1 to 7.4, which required
two API changes:

- `OrderParams` was removed. `requestOtp()`/`sendOtp()` take an array keyed
  by the wire names (`channel`, `destination`, `brand`, `otp_length`, `ttl`,
  `external_id`). Unknown keys throw `\InvalidArgumentException`.
- The `Channel` enum became a constants class: `Channel::WHATSAPP` instead
  of `Channel::WhatsApp`.

Before (v0.1.x):

```php
$res = $client->requestOtp(new OrderParams(
    channel: Channel::WhatsApp,
    destination: '6281234567890',
    externalId: 'order-8821',
));
```

After (v0.2.0 and later):

```php
$res = $client->requestOtp([
    'channel' => Channel::WHATSAPP,
    'destination' => '6281234567890',
    'external_id' => 'order-8821',
]);
```
