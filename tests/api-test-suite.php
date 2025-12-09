<?php
/**
 * Comprehensive API Test Suite for MessageMedia Laravel Package
 * 
 * This test suite verifies:
 * 1. PHP 7.3 compatibility
 * 2. All API endpoints functionality
 * 3. Proxy support
 * 4. Error handling
 * 5. Configuration loading from .env
 * 
 * Run with: php tests/api-test-suite.php
 * Or with Docker: docker-compose -f docker-compose.test.yml up
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Infoxchange\MessageMedia\Client;
use Infoxchange\MessageMedia\Message;
use Infoxchange\MessageMedia\Request\SendMessagesRequest;
use Infoxchange\MessageMedia\Request\CheckRepliesRequest;
use Infoxchange\MessageMedia\Request\CheckDeliveryReportsRequest;
use Infoxchange\MessageMedia\Request\ConfirmRepliesRequest;
use Infoxchange\MessageMedia\Request\ConfirmDeliveryReportsRequest;
use Infoxchange\MessageMedia\Exceptions\ApiException;
use Infoxchange\MessageMedia\Exceptions\ValidationException;
use Infoxchange\MessageMedia\Exceptions\AuthenticationException;

// Load environment variables from .env.test if it exists
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    
    return true;
}

// Try to load .env.test
$envLoaded = loadEnv(__DIR__ . '/../.env.test');
if (!$envLoaded) {
    $envLoaded = loadEnv(__DIR__ . '/../.env');
}

// Test configuration
$config = [
    'apiKey' => getenv('MESSAGEMEDIA_API_KEY') ?: '',
    'apiSecret' => getenv('MESSAGEMEDIA_API_SECRET') ?: '',
    'testRecipient' => getenv('MESSAGEMEDIA_TEST_RECIPIENT') ?: '+61491570156',
    'proxy' => getenv('HTTP_PROXY') ?: null,
    'testMode' => getenv('MESSAGEMEDIA_TEST_MODE') === 'true',
];

// Test results tracking
$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
    'tests' => [],
];

function printHeader($title) {
    echo "\n";
    echo str_repeat('=', 70) . "\n";
    echo $title . "\n";
    echo str_repeat('=', 70) . "\n\n";
}

function printTest($name, $status, $message = '') {
    global $results;
    
    $results['total']++;
    $statusSymbol = '';
    $statusColor = '';
    
    switch ($status) {
        case 'PASS':
            $statusSymbol = '✓';
            $statusColor = "\033[32m"; // Green
            $results['passed']++;
            break;
        case 'FAIL':
            $statusSymbol = '✗';
            $statusColor = "\033[31m"; // Red
            $results['failed']++;
            break;
        case 'SKIP':
            $statusSymbol = '○';
            $statusColor = "\033[33m"; // Yellow
            $results['skipped']++;
            break;
    }
    
    $resetColor = "\033[0m";
    
    echo sprintf(
        "%s%s%s %s\n",
        $statusColor,
        $statusSymbol,
        $resetColor,
        $name
    );
    
    if ($message) {
        echo "  " . $message . "\n";
    }
    
    $results['tests'][] = [
        'name' => $name,
        'status' => $status,
        'message' => $message,
    ];
}

function printSummary() {
    global $results;
    
    printHeader('Test Summary');
    
    echo sprintf("Total Tests:   %d\n", $results['total']);
    echo sprintf("\033[32mPassed:        %d\033[0m\n", $results['passed']);
    echo sprintf("\033[31mFailed:        %d\033[0m\n", $results['failed']);
    echo sprintf("\033[33mSkipped:       %d\033[0m\n", $results['skipped']);
    
    $successRate = $results['total'] > 0 
        ? round(($results['passed'] / $results['total']) * 100, 2) 
        : 0;
    
    echo sprintf("\nSuccess Rate:  %.2f%%\n", $successRate);
    
    if ($results['failed'] > 0) {
        echo "\n\033[31mSome tests failed. Please review the output above.\033[0m\n";
        exit(1);
    } else {
        echo "\n\033[32mAll tests passed successfully!\033[0m\n";
        exit(0);
    }
}

// Start tests
printHeader('MessageMedia API Test Suite - PHP ' . PHP_VERSION);

echo "Environment Configuration:\n";
echo "  API Key:        " . ($config['apiKey'] ? substr($config['apiKey'], 0, 10) . '...' : 'NOT SET') . "\n";
echo "  API Secret:     " . ($config['apiSecret'] ? substr($config['apiSecret'], 0, 10) . '...' : 'NOT SET') . "\n";
echo "  Test Recipient: " . $config['testRecipient'] . "\n";
echo "  Proxy:          " . ($config['proxy'] ?: 'none') . "\n";
echo "  Test Mode:      " . ($config['testMode'] ? 'enabled (no real API calls)' : 'disabled (real API calls)') . "\n";
echo "  Env File:       " . ($envLoaded ? 'loaded' : 'not found (using environment variables)') . "\n";
echo "\n";

// Test 1: PHP Version Check
printHeader('Test 1: PHP Version Compatibility');
try {
    $phpVersion = PHP_VERSION;
    $versionParts = explode('.', $phpVersion);
    $major = (int)$versionParts[0];
    $minor = (int)$versionParts[1];
    
    if ($major === 7 && $minor >= 3) {
        printTest('PHP Version Check', 'PASS', "PHP $phpVersion is compatible");
    } else {
        printTest('PHP Version Check', 'FAIL', "PHP $phpVersion is not compatible (requires 7.3+)");
    }
} catch (\Exception $e) {
    printTest('PHP Version Check', 'FAIL', $e->getMessage());
}

// Test 2: Required Extensions
printHeader('Test 2: Required PHP Extensions');
$requiredExtensions = ['curl', 'json'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        printTest("Extension: $ext", 'PASS', "$ext extension is loaded");
    } else {
        printTest("Extension: $ext", 'FAIL', "$ext extension is not loaded");
    }
}

// Test 3: Configuration Validation
printHeader('Test 3: Configuration Validation');
if (empty($config['apiKey'])) {
    printTest('API Key Configuration', 'FAIL', 'MESSAGEMEDIA_API_KEY is not set');
} else {
    printTest('API Key Configuration', 'PASS', 'API Key is configured');
}

if (empty($config['apiSecret'])) {
    printTest('API Secret Configuration', 'FAIL', 'MESSAGEMEDIA_API_SECRET is not set');
} else {
    printTest('API Secret Configuration', 'PASS', 'API Secret is configured');
}

if (empty($config['testRecipient'])) {
    printTest('Test Recipient Configuration', 'FAIL', 'MESSAGEMEDIA_TEST_RECIPIENT is not set');
} else {
    printTest('Test Recipient Configuration', 'PASS', "Test recipient: {$config['testRecipient']}");
}

// Skip remaining tests if credentials are missing
if (empty($config['apiKey']) || empty($config['apiSecret'])) {
    printTest('Remaining Tests', 'SKIP', 'Skipping API tests due to missing credentials');
    printSummary();
    exit(0);
}

// Test 4: Client Initialization
printHeader('Test 4: Client Initialization');
$client = null;
try {
    $client = new Client(
        $config['apiKey'],
        $config['apiSecret'],
        'https://api.messagemedia.com/v1',
        false,
        $config['proxy']
    );
    printTest('Client Initialization', 'PASS', 'Client created successfully');
    
    if ($config['proxy']) {
        if ($client->getProxy() === $config['proxy']) {
            printTest('Proxy Configuration', 'PASS', "Proxy set to: {$config['proxy']}");
        } else {
            printTest('Proxy Configuration', 'FAIL', 'Proxy not set correctly');
        }
    } else {
        printTest('Proxy Configuration', 'PASS', 'No proxy configured (as expected)');
    }
} catch (\Exception $e) {
    printTest('Client Initialization', 'FAIL', $e->getMessage());
    printSummary();
    exit(1);
}

// Test 5: Send Messages
printHeader('Test 5: Send Messages API');
if ($config['testMode']) {
    printTest('Send Messages', 'SKIP', 'Skipped in test mode');
} else {
    try {
        $message = new Message();
        $message->content = 'Test message from MessageMedia PHP SDK - ' . date('Y-m-d H:i:s');
        $message->destinationNumber = $config['testRecipient'];
        
        $request = new SendMessagesRequest();
        $request->messages = [$message];
        
        $response = $client->sendMessages($request);
        
        if (!empty($response->messages) && !empty($response->messages[0]->messageId)) {
            $messageId = $response->messages[0]->messageId;
            printTest('Send Messages', 'PASS', "Message sent successfully. ID: $messageId");
        } else {
            printTest('Send Messages', 'FAIL', 'No message ID returned');
        }
    } catch (ValidationException $e) {
        printTest('Send Messages', 'FAIL', 'Validation error: ' . $e->getMessage());
    } catch (AuthenticationException $e) {
        printTest('Send Messages', 'FAIL', 'Authentication error: ' . $e->getMessage());
    } catch (ApiException $e) {
        printTest('Send Messages', 'FAIL', 'API error: ' . $e->getMessage());
    } catch (\Exception $e) {
        printTest('Send Messages', 'FAIL', 'Unexpected error: ' . $e->getMessage());
    }
}

// Test 6: Check Replies
printHeader('Test 6: Check Replies API');
if ($config['testMode']) {
    printTest('Check Replies', 'SKIP', 'Skipped in test mode');
} else {
    try {
        $request = new CheckRepliesRequest();
        $request->limit = 10;
        
        $response = $client->checkReplies($request);
        
        if (isset($response->replies)) {
            $count = count($response->replies);
            printTest('Check Replies', 'PASS', "Retrieved $count replies");
        } else {
            printTest('Check Replies', 'FAIL', 'Invalid response structure');
        }
    } catch (AuthenticationException $e) {
        printTest('Check Replies', 'FAIL', 'Authentication error: ' . $e->getMessage());
    } catch (ApiException $e) {
        printTest('Check Replies', 'FAIL', 'API error: ' . $e->getMessage());
    } catch (\Exception $e) {
        printTest('Check Replies', 'FAIL', 'Unexpected error: ' . $e->getMessage());
    }
}

// Test 7: Check Delivery Reports
printHeader('Test 7: Check Delivery Reports API');
if ($config['testMode']) {
    printTest('Check Delivery Reports', 'SKIP', 'Skipped in test mode');
} else {
    try {
        $request = new CheckDeliveryReportsRequest();
        $request->limit = 10;
        
        $response = $client->checkDeliveryReports($request);
        
        if (isset($response->deliveryReports)) {
            $count = count($response->deliveryReports);
            printTest('Check Delivery Reports', 'PASS', "Retrieved $count delivery reports");
        } else {
            printTest('Check Delivery Reports', 'FAIL', 'Invalid response structure');
        }
    } catch (AuthenticationException $e) {
        printTest('Check Delivery Reports', 'FAIL', 'Authentication error: ' . $e->getMessage());
    } catch (ApiException $e) {
        printTest('Check Delivery Reports', 'FAIL', 'API error: ' . $e->getMessage());
    } catch (\Exception $e) {
        printTest('Check Delivery Reports', 'FAIL', 'Unexpected error: ' . $e->getMessage());
    }
}

// Test 8: Proxy Runtime Configuration
printHeader('Test 8: Proxy Runtime Configuration');
try {
    $testProxy = 'http://test-proxy.example.com:8080';
    $client->setProxy($testProxy);
    
    if ($client->getProxy() === $testProxy) {
        printTest('Set Proxy', 'PASS', "Proxy set to: $testProxy");
    } else {
        printTest('Set Proxy', 'FAIL', 'Proxy not set correctly');
    }
    
    $client->setProxy(null);
    if ($client->getProxy() === null) {
        printTest('Remove Proxy', 'PASS', 'Proxy removed successfully');
    } else {
        printTest('Remove Proxy', 'FAIL', 'Proxy not removed correctly');
    }
    
    // Restore original proxy if it was set
    if ($config['proxy']) {
        $client->setProxy($config['proxy']);
    }
} catch (\Exception $e) {
    printTest('Proxy Runtime Configuration', 'FAIL', $e->getMessage());
}

// Test 9: Error Handling
printHeader('Test 9: Error Handling');
try {
    // Test with invalid credentials
    $badClient = new Client('invalid_key', 'invalid_secret');
    $message = new Message();
    $message->content = 'Test';
    $message->destinationNumber = $config['testRecipient'];
    
    $request = new SendMessagesRequest();
    $request->messages = [$message];
    
    try {
        $badClient->sendMessages($request);
        printTest('Authentication Error Handling', 'FAIL', 'Should have thrown AuthenticationException');
    } catch (AuthenticationException $e) {
        printTest('Authentication Error Handling', 'PASS', 'AuthenticationException caught correctly');
    }
} catch (\Exception $e) {
    printTest('Error Handling', 'FAIL', $e->getMessage());
}

// Test 10: Validation
printHeader('Test 10: Input Validation');
try {
    $message = new Message();
    $message->content = ''; // Empty content
    $message->destinationNumber = $config['testRecipient'];
    
    $request = new SendMessagesRequest();
    $request->messages = [$message];
    
    try {
        $client->sendMessages($request);
        printTest('Empty Content Validation', 'FAIL', 'Should have thrown ValidationException');
    } catch (ValidationException $e) {
        printTest('Empty Content Validation', 'PASS', 'ValidationException caught correctly');
    }
} catch (\Exception $e) {
    printTest('Input Validation', 'FAIL', $e->getMessage());
}

try {
    $message = new Message();
    $message->content = 'Test';
    $message->destinationNumber = 'invalid'; // Invalid phone number
    
    $request = new SendMessagesRequest();
    $request->messages = [$message];
    
    try {
        $client->sendMessages($request);
        printTest('Invalid Phone Number Validation', 'FAIL', 'Should have thrown ValidationException');
    } catch (ValidationException $e) {
        printTest('Invalid Phone Number Validation', 'PASS', 'ValidationException caught correctly');
    }
} catch (\Exception $e) {
    printTest('Input Validation', 'FAIL', $e->getMessage());
}

// Print final summary
printSummary();
