# Sub-Account & Sender Address Support Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add MessageMedia sub-account header injection and client-level sender address defaulting to the `laravel-messagemedia` SDK.

**Architecture:** Two new nullable constructor params (`$subAccountId`, `$senderAddress`) are added to `Client` and `HttpClient`, following the same pattern as the existing `$proxyUrl` param. `HttpClient::buildHeaders()` injects `Account: <id>` when set. `Client::messageToArray()` defaults `source_number` from `$senderAddress` when the individual message doesn't supply one. `ServiceProvider` reads two new config keys.

**Tech Stack:** PHP 7.3+, Laravel 6+, PHPUnit 8/9, raw ext-curl (no Guzzle).

---

### Task 1: HttpClient — add `$subAccountId` property and constructor param

**Files:**
- Modify: `src/Http/HttpClient.php`

**Step 1: Add the property and constructor param**

In `src/Http/HttpClient.php`, after the `/** @var string|null */ private $proxyUrl;` property declaration, add:

```php
/** @var string|null */
private $subAccountId;
```

Update the constructor docblock and signature — add `$subAccountId = null` as the **last** param after `$proxyUrl`:

```php
/**
 * @param string $apiKey
 * @param string $apiSecret
 * @param string $baseUrl
 * @param bool $useHmac
 * @param int $timeout
 * @param bool $verifySsl
 * @param string|null $proxyUrl
 * @param string|null $subAccountId
 */
public function __construct(
    $apiKey,
    $apiSecret,
    $baseUrl = 'https://api.messagemedia.com/v1',
    $useHmac = false,
    $timeout = 30,
    $verifySsl = true,
    $proxyUrl = null,
    $subAccountId = null
) {
```

At the end of the constructor body, add:

```php
$this->subAccountId = $subAccountId;
```

**Step 2: Add getter and setter** (after the existing `getProxy()` / `setProxy()` methods):

```php
/**
 * Set the sub-account ID for the Account header
 *
 * @param string|null $subAccountId
 * @return void
 */
public function setSubAccount($subAccountId)
{
    $this->subAccountId = $subAccountId;
}

/**
 * Get the current sub-account ID
 *
 * @return string|null
 */
public function getSubAccount()
{
    return $this->subAccountId;
}
```

**Step 3: No tests yet — just verify no existing tests break**

```bash
./vendor/bin/phpunit
```

Expected: all existing tests pass.

**Step 4: Commit**

```bash
git add src/Http/HttpClient.php
git commit -m "feat: add subAccountId property and constructor param to HttpClient"
```

---

### Task 2: HttpClient — inject `Account:` header in `buildHeaders()`

**Files:**
- Modify: `src/Http/HttpClient.php`

**Step 1: Write the failing test**

In `tests/Unit/ClientTest.php`, add two tests. The existing unit tests use `Client` directly, but `buildHeaders()` is private on `HttpClient`. Use PHP's `ReflectionMethod` to call it in isolation. Add these tests after the existing ones:

```php
/**
 * @test
 */
public function it_includes_account_header_when_sub_account_configured()
{
    $httpClient = new \Infoxchange\MessageMedia\Http\HttpClient(
        'key', 'secret',
        'https://api.messagemedia.com/v1',
        false, 30, true, null,
        'Infoxchange_25380_0003'
    );

    $method = new \ReflectionMethod($httpClient, 'buildHeaders');
    $method->setAccessible(true);
    $headers = $method->invoke($httpClient, null);

    $this->assertContains('Account: Infoxchange_25380_0003', $headers);
}

/**
 * @test
 */
public function it_omits_account_header_when_sub_account_not_configured()
{
    $httpClient = new \Infoxchange\MessageMedia\Http\HttpClient(
        'key', 'secret'
    );

    $method = new \ReflectionMethod($httpClient, 'buildHeaders');
    $method->setAccessible(true);
    $headers = $method->invoke($httpClient, null);

    foreach ($headers as $header) {
        $this->assertStringNotContainsString('Account:', $header);
    }
}
```

**Step 2: Run to verify they fail**

```bash
./vendor/bin/phpunit --filter "it_includes_account_header_when_sub_account_configured|it_omits_account_header_when_sub_account_not_configured"
```

Expected: `it_includes_account_header_when_sub_account_configured` FAILS (no Account header yet). `it_omits_account_header_when_sub_account_not_configured` PASSES (already true).

**Step 3: Implement — inject header in `buildHeaders()`**

In `HttpClient::buildHeaders()`, after the HMAC block, add:

```php
if (!empty($this->subAccountId)) {
    $headers[] = "Account: {$this->subAccountId}";
}
```

Full updated `buildHeaders()`:

```php
private function buildHeaders($body = null)
{
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    $credentials = base64_encode("{$this->apiKey}:{$this->apiSecret}");
    $headers[] = "Authorization: Basic {$credentials}";

    if ($this->useHmac && $body) {
        $signature = hash_hmac('sha256', $body, $this->apiSecret);
        $headers[] = "X-MessageMedia-Signature: {$signature}";
    }

    if (!empty($this->subAccountId)) {
        $headers[] = "Account: {$this->subAccountId}";
    }

    return $headers;
}
```

**Step 4: Run tests to verify they pass**

```bash
./vendor/bin/phpunit --filter "it_includes_account_header_when_sub_account_configured|it_omits_account_header_when_sub_account_not_configured"
```

Expected: both PASS.

**Step 5: Run full suite**

```bash
./vendor/bin/phpunit
```

Expected: all tests pass.

**Step 6: Commit**

```bash
git add src/Http/HttpClient.php tests/Unit/ClientTest.php
git commit -m "feat: inject Account header for sub-account requests in HttpClient"
```

---

### Task 3: Client — add `$subAccountId` and `$senderAddress` params

**Files:**
- Modify: `src/Client.php`

**Step 1: Add properties**

After the `/** @var string|null */ private $proxyUrl;` property in `Client`, add:

```php
/** @var string|null */
private $subAccountId;

/** @var string|null */
private $senderAddress;
```

**Step 2: Update constructor docblock and signature**

Replace the existing constructor signature:

```php
/**
 * @param string $apiKey
 * @param string $apiSecret
 * @param string $baseUrl
 * @param bool $useHmac
 * @param string|null $proxyUrl
 * @param string|null $subAccountId
 * @param string|null $senderAddress
 */
public function __construct(
    $apiKey,
    $apiSecret,
    $baseUrl = 'https://api.messagemedia.com/v1',
    $useHmac = false,
    $proxyUrl = null,
    $subAccountId = null,
    $senderAddress = null
) {
    $this->apiKey = $apiKey;
    $this->apiSecret = $apiSecret;
    $this->baseUrl = rtrim($baseUrl, '/');
    $this->useHmac = $useHmac;
    $this->proxyUrl = $proxyUrl;
    $this->subAccountId = $subAccountId;
    $this->senderAddress = $senderAddress;

    $this->httpClient = new HttpClient(
        $apiKey,
        $apiSecret,
        $this->baseUrl,
        $useHmac,
        30, // timeout
        true, // verifySsl
        $proxyUrl,
        $subAccountId
    );
}
```

**Step 3: Add getters/setters**

After the existing `getProxy()` / `setProxy()` methods, add:

```php
/**
 * Set the sub-account ID used in the Account header
 *
 * @param string|null $subAccountId
 * @return void
 */
public function setSubAccount($subAccountId)
{
    $this->subAccountId = $subAccountId;
    $this->httpClient->setSubAccount($subAccountId);
}

/**
 * Get the current sub-account ID
 *
 * @return string|null
 */
public function getSubAccount()
{
    return $this->subAccountId;
}

/**
 * Set the sender address used as source_number for all outbound messages
 *
 * @param string|null $senderAddress E.164 phone number e.g. +61491570001
 * @return void
 */
public function setSenderAddress($senderAddress)
{
    $this->senderAddress = $senderAddress;
}

/**
 * Get the current sender address
 *
 * @return string|null
 */
public function getSenderAddress()
{
    return $this->senderAddress;
}
```

**Step 4: Run full test suite — no new tests yet, verify nothing broke**

```bash
./vendor/bin/phpunit
```

Expected: all tests pass.

**Step 5: Commit**

```bash
git add src/Client.php
git commit -m "feat: add subAccountId and senderAddress params to Client"
```

---

### Task 4: Client — inject sender address into outbound message payload

**Files:**
- Modify: `src/Client.php`
- Test: `tests/Unit/ClientTest.php`

**Step 1: Write the failing tests**

Add to `tests/Unit/ClientTest.php`:

```php
/**
 * @test
 */
public function it_injects_sender_address_into_message_payload()
{
    $client = new Client('key', 'secret', 'https://api.messagemedia.com/v1', false, null, null, '+61491570001');

    $method = new \ReflectionMethod($client, 'messageToArray');
    $method->setAccessible(true);

    $message = new \Infoxchange\MessageMedia\Message();
    $message->content = 'Hello';
    $message->destinationNumber = '+61491570156';

    $array = $method->invoke($client, $message);

    $this->assertEquals('+61491570001', $array['source_number']);
}

/**
 * @test
 */
public function it_does_not_override_per_message_source_number()
{
    $client = new Client('key', 'secret', 'https://api.messagemedia.com/v1', false, null, null, '+61491570001');

    $method = new \ReflectionMethod($client, 'messageToArray');
    $method->setAccessible(true);

    $message = new \Infoxchange\MessageMedia\Message();
    $message->content = 'Hello';
    $message->destinationNumber = '+61491570156';
    $message->sourceNumber = '+61491570999'; // explicit per-message override

    $array = $method->invoke($client, $message);

    $this->assertEquals('+61491570999', $array['source_number']);
}

/**
 * @test
 */
public function it_sets_and_gets_sub_account()
{
    $client = new Client('key', 'secret');
    $client->setSubAccount('MySubAccount');
    $this->assertEquals('MySubAccount', $client->getSubAccount());
}

/**
 * @test
 */
public function it_sets_and_gets_sender_address()
{
    $client = new Client('key', 'secret');
    $client->setSenderAddress('+61491570001');
    $this->assertEquals('+61491570001', $client->getSenderAddress());
}
```

**Step 2: Run to verify they fail**

```bash
./vendor/bin/phpunit --filter "it_injects_sender_address|it_does_not_override|it_sets_and_gets_sub_account|it_sets_and_gets_sender_address"
```

Expected: `it_injects_sender_address_into_message_payload` FAILS. Others may pass (getter/setter tests) or fail depending on state.

**Step 3: Implement in `Client::messageToArray()`**

In `Client::messageToArray()`, after the final `if (!empty($message->messageExpiryTimestamp))` block, add:

```php
// Apply client-level sender address default if message doesn't supply one
if (empty($data['source_number']) && !empty($this->senderAddress)) {
    $data['source_number'] = $this->senderAddress;
}
```

**Step 4: Run targeted tests**

```bash
./vendor/bin/phpunit --filter "it_injects_sender_address|it_does_not_override|it_sets_and_gets_sub_account|it_sets_and_gets_sender_address"
```

Expected: all 4 PASS.

**Step 5: Run full suite**

```bash
./vendor/bin/phpunit
```

Expected: all tests pass.

**Step 6: Commit**

```bash
git add src/Client.php tests/Unit/ClientTest.php
git commit -m "feat: inject client-level sender address into outbound message payload"
```

---

### Task 5: Config & ServiceProvider

**Files:**
- Modify: `config/messagemedia.php`
- Modify: `src/ServiceProvider.php`

**Step 1: Add new config keys to `config/messagemedia.php`**

After the `'proxy'` entry, add:

```php
/*
 * MessageMedia sub-account ID (optional)
 * When set, all requests include the Account: header to act on behalf of this sub-account.
 * Example: 'Infoxchange_25380_0003'
 */
'sub_account' => env('MESSAGEMEDIA_SUB_ACCOUNT'),

/*
 * Default sender address (optional)
 * E.164 phone number used as source_number for all outbound messages.
 * Overridden per-message via Message::sourceNumber.
 * Example: '+61491570001'
 */
'sender_address' => env('MESSAGEMEDIA_SENDER_ADDRESS'),
```

**Step 2: Update `ServiceProvider` to pass new config values**

In `src/ServiceProvider.php`, update the `Client` instantiation:

```php
$this->app->singleton('messagemedia', function ($app) {
    return new Client(
        config('messagemedia.api_key'),
        config('messagemedia.api_secret'),
        config('messagemedia.base_url'),
        config('messagemedia.use_hmac', false),
        config('messagemedia.proxy'),
        config('messagemedia.sub_account'),
        config('messagemedia.sender_address')
    );
});
```

**Step 3: Run full suite**

```bash
./vendor/bin/phpunit
```

Expected: all tests pass.

**Step 4: Commit**

```bash
git add config/messagemedia.php src/ServiceProvider.php
git commit -m "feat: add sub_account and sender_address config keys and pass to Client"
```

---

### Task 6: Update `api-test-suite.php` with sub-account and sender address tests

**Files:**
- Modify: `tests/api-test-suite.php`

**Step 1: Add config keys to `$config` array** (around line 62)

Add after `'testMode'`:

```php
'subAccount'    => getenv('MESSAGEMEDIA_SUB_ACCOUNT') ?: null,
'senderAddress' => getenv('MESSAGEMEDIA_SENDER_ADDRESS') ?: null,
```

**Step 2: Update environment info display** to show the new values (find the block that prints proxy/testMode and add after it):

```php
echo "  Sub-Account:    " . ($config['subAccount'] ? substr($config['subAccount'], 0, 10) . '...' : 'none') . "\n";
echo "  Sender Address: " . ($config['senderAddress'] ?: 'none') . "\n";
```

**Step 3: Add a new test block** after Test 10 (Proxy Runtime Configuration), before Test 11 (Error Handling):

```php
// Test 11: Sub-Account and Sender Address Configuration
printHeader('Test 11: Sub-Account & Sender Address Configuration');

// Sub-account getter/setter
$testClient = new Client($config['apiKey'], $config['apiSecret']);
$testClient->setSubAccount('TestSubAccount');
if ($testClient->getSubAccount() === 'TestSubAccount') {
    printTest('Sub-Account Setter/Getter', 'PASS', 'Sub-account round-trip works');
} else {
    printTest('Sub-Account Setter/Getter', 'FAIL', 'Sub-account not stored correctly');
}

// Sender address getter/setter
$testClient->setSenderAddress('+61491570001');
if ($testClient->getSenderAddress() === '+61491570001') {
    printTest('Sender Address Setter/Getter', 'PASS', 'Sender address round-trip works');
} else {
    printTest('Sender Address Setter/Getter', 'FAIL', 'Sender address not stored correctly');
}

// Sub-account client initialisation from constructor
if ($config['subAccount']) {
    $subClient = new Client(
        $config['apiKey'],
        $config['apiSecret'],
        'https://api.messagemedia.com/v1',
        false,
        $config['proxy'],
        $config['subAccount'],
        $config['senderAddress']
    );
    if ($subClient->getSubAccount() === $config['subAccount']) {
        printTest('Sub-Account Constructor Init', 'PASS', "Sub-account: {$config['subAccount']}");
    } else {
        printTest('Sub-Account Constructor Init', 'FAIL', 'Sub-account not set from constructor');
    }
} else {
    printTest('Sub-Account Constructor Init', 'SKIP', 'MESSAGEMEDIA_SUB_ACCOUNT not configured');
}

// Live send via sub-account (only if both configured and not test mode)
if ($config['subAccount'] && !$config['testMode']) {
    try {
        $subClient = new Client(
            $config['apiKey'],
            $config['apiSecret'],
            'https://api.messagemedia.com/v1',
            false,
            $config['proxy'],
            $config['subAccount'],
            $config['senderAddress']
        );

        $message = new Message();
        $message->content = 'Sub-account test from MessageMedia PHP SDK - ' . date('Y-m-d H:i:s');
        $message->destinationNumber = $config['testRecipient'];

        $request = new SendMessagesRequest();
        $request->messages = [$message];

        $response = $subClient->sendMessages($request);

        if (!empty($response->messages) && !empty($response->messages[0]->messageId)) {
            printTest('Send via Sub-Account', 'PASS', "Message ID: {$response->messages[0]->messageId}");
        } else {
            printTest('Send via Sub-Account', 'FAIL', 'No message ID returned');
        }
    } catch (\Exception $e) {
        printTest('Send via Sub-Account', 'FAIL', $e->getMessage());
    }
} else {
    $reason = $config['testMode'] ? 'test mode enabled' : 'MESSAGEMEDIA_SUB_ACCOUNT not configured';
    printTest('Send via Sub-Account', 'SKIP', "Skipped: $reason");
}
```

> **Note:** Renumber the existing Test 11 (Error Handling) and Test 12 (Input Validation) to Test 12 and Test 13 in their `printHeader()` calls.

**Step 4: Update `.env.test.example`** to document the new variables. Open `.env.test.example` and add:

```env
# Sub-account ID (optional) - e.g. Infoxchange_25380_0003
MESSAGEMEDIA_SUB_ACCOUNT=

# Sender address (optional) - E.164 format e.g. +61491570001
MESSAGEMEDIA_SENDER_ADDRESS=
```

**Step 5: Run suite in test mode to confirm no regressions**

```bash
MESSAGEMEDIA_TEST_MODE=true php tests/api-test-suite.php
```

Expected: all live-API tests SKIP, new configuration/getter tests PASS.

**Step 6: Commit**

```bash
git add tests/api-test-suite.php .env.test.example
git commit -m "feat: add sub-account and sender address tests to api-test-suite"
```

---

### Task 7: Update README and CHANGELOG, push

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`

**Step 1: Add to README**

Find the Configuration section in `README.md` and add the two new env vars in the configuration table/list alongside existing ones:

```
MESSAGEMEDIA_SUB_ACCOUNT    - (optional) Sub-account ID; sets Account: header on all requests
MESSAGEMEDIA_SENDER_ADDRESS - (optional) Default source phone number for outbound messages (E.164)
```

Also add a short usage example showing how to construct a `Client` with these values, or set them via `setSubAccount()` / `setSenderAddress()`.

**Step 2: Add CHANGELOG entry**

At the top of `CHANGELOG.md` (after any existing header), add:

```markdown
## [Unreleased]

### Added
- Sub-account support: pass `MESSAGEMEDIA_SUB_ACCOUNT` (or constructor param `$subAccountId`) to inject the `Account:` header on all requests, enabling parent-account credentials to act on behalf of a named sub-account.
- Sender address default: pass `MESSAGEMEDIA_SENDER_ADDRESS` (or constructor param `$senderAddress`) to automatically set `source_number` on all outbound messages when not specified per-message.
- `Client::setSubAccount()` / `getSubAccount()` and `setSenderAddress()` / `getSenderAddress()` methods.
- `HttpClient::setSubAccount()` / `getSubAccount()` methods.
```

**Step 3: Run full suite one final time**

```bash
./vendor/bin/phpunit
```

Expected: all tests pass.

**Step 4: Commit and push**

```bash
git add README.md CHANGELOG.md
git commit -m "docs: update README and CHANGELOG for sub-account and sender address support"
git push origin main
```
