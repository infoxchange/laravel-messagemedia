# API Test Suite

Comprehensive test suite for the MessageMedia Laravel package with Docker support for PHP 7.3 compatibility testing.

## Quick Start

### 1. Setup Environment

Copy the example environment file and configure your credentials:

```bash
cp .env.test.example .env.test
```

Edit `.env.test` and add your MessageMedia API credentials:

```env
MESSAGEMEDIA_API_KEY=your_api_key_here
MESSAGEMEDIA_API_SECRET=your_api_secret_here
MESSAGEMEDIA_TEST_RECIPIENT=+61491570156
```

### 2. Run Tests Locally

```bash
php tests/api-test-suite.php
```

### 3. Run Tests in Docker (PHP 7.3)

Build and run the test container:

```bash
# Build the Docker image
docker-compose -f docker-compose.test.yml build

# Run the tests
docker-compose -f docker-compose.test.yml up

# Or run in one command
docker-compose -f docker-compose.test.yml up --build
```

## Test Coverage

The test suite covers:

### 1. **PHP Version Compatibility**
- Verifies PHP 7.3+ compatibility
- Checks required extensions (curl, json)

### 2. **Configuration Validation**
- API key configuration
- API secret configuration
- Test recipient configuration
- Environment variable loading

### 3. **Client Initialization**
- Client creation
- Proxy configuration
- Runtime proxy management

### 4. **Send Messages API**
- Message sending
- Response validation
- Message ID verification

### 5. **Check Replies API**
- Retrieve incoming replies
- Response structure validation

### 6. **Check Delivery Reports API**
- Retrieve delivery reports
- Response structure validation

### 7. **Proxy Support**
- HTTP_PROXY environment variable
- Runtime proxy configuration
- Proxy removal

### 8. **Error Handling**
- Authentication errors
- Validation errors
- API errors

### 9. **Input Validation**
- Empty content validation
- Invalid phone number validation
- Required field validation

## Test Modes

### Real API Mode (Default)

Tests make actual API calls to MessageMedia:

```bash
MESSAGEMEDIA_TEST_MODE=false php tests/api-test-suite.php
```

### Test Mode (No API Calls)

Skips actual API calls for testing configuration only:

```bash
MESSAGEMEDIA_TEST_MODE=true php tests/api-test-suite.php
```

## Proxy Testing

To test with a proxy:

```bash
HTTP_PROXY=http://proxy.example.com:8080 php tests/api-test-suite.php
```

Or add to `.env.test`:

```env
HTTP_PROXY=http://proxy.example.com:8080
```

## Docker Commands

### Build Only

```bash
docker-compose -f docker-compose.test.yml build
```

### Run Tests

```bash
docker-compose -f docker-compose.test.yml up
```

### Run with Environment Variables

```bash
docker-compose -f docker-compose.test.yml run \
  -e MESSAGEMEDIA_API_KEY=your_key \
  -e MESSAGEMEDIA_API_SECRET=your_secret \
  php73-test
```

### Clean Up

```bash
docker-compose -f docker-compose.test.yml down
docker rmi messagemedia-php73-test
```

## Test Output

The test suite provides colored output:

- ✓ **Green** - Test passed
- ✗ **Red** - Test failed
- ○ **Yellow** - Test skipped

Example output:

```
======================================================================
MessageMedia API Test Suite - PHP 7.3.33
======================================================================

Environment Configuration:
  API Key:        vJPfFORHpi...
  API Secret:     PYSZaifCVp...
  Test Recipient: +61491570156
  Proxy:          none
  Test Mode:      disabled (real API calls)
  Env File:       loaded

======================================================================
Test 1: PHP Version Compatibility
======================================================================

✓ PHP Version Check
  PHP 7.3.33 is compatible

======================================================================
Test 2: Required PHP Extensions
======================================================================

✓ Extension: curl
  curl extension is loaded
✓ Extension: json
  json extension is loaded

...

======================================================================
Test Summary
======================================================================

Total Tests:   20
Passed:        20
Failed:        0
Skipped:       0

Success Rate:  100.00%

All tests passed successfully!
```

## Troubleshooting

### Missing Credentials

If you see:

```
✗ API Key Configuration
  MESSAGEMEDIA_API_KEY is not set
```

Solution: Create `.env.test` file with your credentials.

### Docker Build Fails

If Docker build fails:

```bash
# Clean up and rebuild
docker-compose -f docker-compose.test.yml down
docker system prune -f
docker-compose -f docker-compose.test.yml build --no-cache
```

### Permission Errors

If you get permission errors:

```bash
chmod +x tests/api-test-suite.php
```

### Proxy Connection Errors

If proxy tests fail:

1. Verify proxy URL format: `http://host:port`
2. Check proxy is accessible
3. Test proxy with curl:

```bash
curl -x http://proxy:port https://api.messagemedia.com
```

## CI/CD Integration

### GitHub Actions

Add to `.github/workflows/test.yml`:

```yaml
name: API Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Create .env.test
        run: |
          echo "MESSAGEMEDIA_API_KEY=${{ secrets.MESSAGEMEDIA_API_KEY }}" >> .env.test
          echo "MESSAGEMEDIA_API_SECRET=${{ secrets.MESSAGEMEDIA_API_SECRET }}" >> .env.test
          echo "MESSAGEMEDIA_TEST_RECIPIENT=${{ secrets.MESSAGEMEDIA_TEST_RECIPIENT }}" >> .env.test
      
      - name: Run tests in Docker
        run: docker-compose -f docker-compose.test.yml up --abort-on-container-exit
```

### GitLab CI

Add to `.gitlab-ci.yml`:

```yaml
test:
  image: docker:latest
  services:
    - docker:dind
  script:
    - docker-compose -f docker-compose.test.yml up --abort-on-container-exit
  variables:
    MESSAGEMEDIA_API_KEY: $MESSAGEMEDIA_API_KEY
    MESSAGEMEDIA_API_SECRET: $MESSAGEMEDIA_API_SECRET
    MESSAGEMEDIA_TEST_RECIPIENT: $MESSAGEMEDIA_TEST_RECIPIENT
```

## Contributing

When adding new features:

1. Add corresponding tests to `tests/api-test-suite.php`
2. Update this README with new test coverage
3. Ensure all tests pass before submitting PR

## Support

For issues with the test suite:

1. Check this README
2. Review test output for specific errors
3. Open an issue at https://github.com/infoxchange/laravel-messagemedia/issues
