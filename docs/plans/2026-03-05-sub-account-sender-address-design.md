# Design: Sub-Account & Sender Address Support

**Ticket:** VACCHO — MessageMedia sub-account and sender address support  
**Scope:** SDK-only (`laravel-messagemedia` package). SRS XML config parsing and mapping is a separate SRS-side concern.

## Problem

All ACCO clusters currently share MessageMedia's random number pool, so clients receive SMS messages from numbers also used by unrelated businesses. MessageMedia supports two features that solve this:

1. **Sub-accounts** — a parent MM account can act on behalf of a named sub-account by injecting an `Account:` header, giving each cluster its own identity.
2. **Sender address** — a specific source phone number can be set so messages consistently come from a known number rather than a random pool number.

Neither feature is currently supported by this SDK.

## Approach

Extend `Client` and `HttpClient` constructors with two new optional params (`$subAccountId`, `$senderAddress`), following the same pattern used for `$proxyUrl`. Both default to `null` (backward-compatible). `ServiceProvider` reads them from new config keys.

## Design

### 1. Config (`config/messagemedia.php`)

Two new nullable keys:

```php
'sub_account'    => env('MESSAGEMEDIA_SUB_ACCOUNT'),    // null = use main account
'sender_address' => env('MESSAGEMEDIA_SENDER_ADDRESS'), // null = MM random pool
```

### 2. Constructor Changes

**`HttpClient::__construct()`** — new trailing param:
```php
$subAccountId = null  // injected as Account: header on every request
```

**`Client::__construct()`** — two new trailing params:
```php
$subAccountId = null,    // forwarded to HttpClient
$senderAddress = null    // used to default source_number in outbound messages
```

Both are nullable and default to `null`, so existing callers are unaffected.

### 3. Sub-Account Header (`HttpClient::buildHeaders()`)

When `$this->subAccountId` is non-empty, append to every request:

```
Account: <subAccountId>
```

Applies to all currently-exposed endpoints: sendMessages, checkReplies, confirmReplies, checkDeliveryReports, confirmDeliveryReports, getMessageStatus, cancelMessage, getCredits.

The test sub-account ID for validation: `Infoxchange_25380_0003`.

### 4. Sender Address in Payload (`Client::messageToArray()`)

After existing optional field mapping, inject `source_number` from the client-level default if the message hasn't already set one:

```php
if (empty($data['source_number']) && !empty($this->senderAddress)) {
    $data['source_number'] = $this->senderAddress;
}
```

Per-message `Message::sourceNumber` takes precedence. The client default only fills the gap.

### 5. Getters & Setters

Add to `Client`:
- `getSubAccount()` / `setSubAccount($subAccountId)`
- `getSenderAddress()` / `setSenderAddress($senderAddress)`

Add to `HttpClient`:
- `getSubAccount()` / `setSubAccount($subAccountId)`

Mirrors the existing `getProxy()` / `setProxy()` pattern.

### 6. ServiceProvider

Pass new config values to `Client`:

```php
new Client(
    config('messagemedia.api_key'),
    config('messagemedia.api_secret'),
    config('messagemedia.base_url'),
    config('messagemedia.use_hmac', false),
    config('messagemedia.proxy'),
    config('messagemedia.sub_account'),    // NEW
    config('messagemedia.sender_address')  // NEW
);
```

## Tests

### Unit tests (PHPUnit, `tests/Unit/ClientTest.php`)

| Test | Description |
|------|-------------|
| `it_sets_sub_account_header_when_configured` | HttpClient built with sub-account ID includes `Account:` header |
| `it_omits_account_header_when_sub_account_not_configured` | No `Account:` header when `subAccountId` is null |
| `it_injects_sender_address_into_message_payload` | `source_number` is set from client default when Message has none |
| `it_does_not_override_per_message_source_number` | `Message::sourceNumber` takes precedence over client default |
| `it_sets_and_gets_sub_account` | Getter/setter round-trip on Client |
| `it_sets_and_gets_sender_address` | Getter/setter round-trip on Client |

### Integration tests (`tests/api-test-suite.php`)

New test cases exercising sub-account and sender address when `MESSAGEMEDIA_SUB_ACCOUNT` / `MESSAGEMEDIA_SENDER_ADDRESS` env vars are present. Skipped in test mode.

## Out of Scope

- SRS XML config attributes (`srsSmsAccountType`, sender ID fields) — SRS application concern
- MessageMedia Sender ID registration API (`POST /v1/sender_addresses`) — separate feature
- Per-request sub-account or sender address override
