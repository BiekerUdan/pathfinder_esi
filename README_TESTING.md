# Running ESI Integration Tests with PHP 7.4

## Quick Start

### 1. Install Dependencies

```bash
composer install --ignore-platform-req=php
```

### 2. Get ESI Credentials

Run the token helper script:

```bash
php get_token.php
```

Follow the prompts to get your refresh token.

### 3. Configure Environment

```bash
cp .env.example .env
# Edit .env with your credentials from step 2
```

### 4. Run Tests with PHP 7.4

```bash
# Run all tests
./phpunit74

# Run specific test class
./phpunit74 tests/Integration/PublicEndpointsTest.php

# Run specific test method
./phpunit74 --filter testGetServerStatus

# Verbose output
./phpunit74 --verbose
```

## Why PHP 7.4?

The Pathfinder application runs on PHP 7.4, so we test with that version to ensure compatibility. The dependencies are installed with your system PHP (8.4) but tests run with PHP 7.4 using the `./phpunit74` wrapper script.

## Troubleshooting

### "Warning: preg_replace(): Allocation of JIT memory failed"

This is a harmless macOS security warning. Tests will still run fine. To suppress it, add to `/usr/local/php74/lib/php.ini`:

```ini
pcre.jit=0
```

### "No such file /usr/local/php74/bin/php"

Update the path in `phpunit74` script to match your PHP 7.4 installation location.

### "ESI credentials not configured"

Make sure you've created a `.env` file with all required values. See `.env.example` for the template.

## Test Documentation

See `tests/README.md` for detailed information about the test suite, setup instructions, and test coverage.
