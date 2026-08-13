# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.1.0] - 2026-08-14

### Added

- Initial release of the OTP.ID PHP SDK.
- `Client` covering all six V3 API endpoints: `requestOtp()`, `sendOtp()`,
  `verifyOtp()`, `otpStatus()`, `account()`, `createTopup()`.
- `Webhook::parseVerifiedEvent()` and `Webhook::verifySignature()` for the
  `otp.verified` webhook — HMAC-SHA256 signature check (constant-time) and
  ±5 minute replay-window validation by default, configurable via
  `$tolerance`.
- `Channel` enum and readonly result classes: `OrderResult`, `VerifyResult`,
  `StatusResult`, `AccountResult`, `TopupResult`, `Verification`,
  `VerifiedEvent`.
- `ApiException` (with `getErrorCode()`, `getHttpStatus()`, `getDetails()`)
  plus the supporting exception hierarchy: `ConnectionException`,
  `InvalidSignatureException`, `StaleTimestampException`,
  `UnexpectedEventException`.
- Default `CurlTransport` with a pluggable `TransportInterface` for custom
  HTTP clients.
- Zero runtime dependencies — only `ext-curl` and `ext-json`.
- Runnable per-channel examples in `examples/`.
