<?php

declare(strict_types=1);

namespace OtpId;

/**
 * Request body for requestOtp() and sendOtp().
 */
final class OrderParams
{
    /**
     * @param Channel $channel selects the delivery channel; required
     * @param ?string $destination phone number (digits only) or email address; required for every channel except Channel::WhatsAppInbound
     * @param ?string $brand overrides the merchant brand_name shown in the OTP message; required by the server for Channel::Voice
     * @param ?int $otpLength generated code length; server default 6, clamped 4-8 (the server forces 4 for Channel::Voice)
     * @param ?int $ttl OTP validity in seconds; server default 300, clamped 60-900
     * @param ?string $externalId optional merchant-side idempotency key
     */
    public function __construct(
        public readonly Channel $channel,
        public readonly ?string $destination = null,
        public readonly ?string $brand = null,
        public readonly ?int $otpLength = null,
        public readonly ?int $ttl = null,
        public readonly ?string $externalId = null,
    ) {
    }

    /**
     * Wire representation sent to the API. Null and empty-string values are
     * dropped — the PHP equivalent of Go's `omitempty` struct tags.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $wire = ['channel' => $this->channel->value];

        foreach ([
            'destination' => $this->destination,
            'brand' => $this->brand,
            'otp_length' => $this->otpLength,
            'ttl' => $this->ttl,
            'external_id' => $this->externalId,
        ] as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $wire[$key] = $value;
        }

        return $wire;
    }
}
