# ESI Integration Tests

This directory contains integration tests for the EVE Online ESI API client library. These tests make real API calls to the ESI endpoints using OAuth2 authentication.

## Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Create ESI Application

1. Go to https://developers.eveonline.com/applications
2. Create a new application
3. Set the callback URL (e.g., `http://localhost/callback`)
4. Add the following scopes:
   - `esi-location.read_location.v1`
   - `esi-location.read_ship_type.v1`
   - `esi-location.read_online.v1`
   - `esi-clones.read_clones.v1`
   - `esi-characters.read_corporation_roles.v1`
   - `esi-ui.write_waypoint.v1`
   - `esi-ui.open_window.v1`
   - `esi-search.search_structures.v1`
   - `esi-universe.read_structures.v1`
5. Note your **Client ID** and **Client Secret**

### 3. Get Refresh Token

You need to obtain a refresh token by completing the OAuth2 flow. Here's a quick way to do it:

#### Option A: Use a Helper Script

Create a file `get_token.php` in the root directory:

```php
<?php
require 'vendor/autoload.php';

$clientId = 'YOUR_CLIENT_ID';
$clientSecret = 'YOUR_CLIENT_SECRET';
$callbackUrl = 'http://localhost/callback';

$scopes = [
    'esi-location.read_location.v1',
    'esi-location.read_ship_type.v1',
    'esi-location.read_online.v1',
    'esi-clones.read_clones.v1',
    'esi-characters.read_corporation_roles.v1',
    'esi-ui.write_waypoint.v1',
    'esi-ui.open_window.v1',
    'esi-search.search_structures.v1',
    'esi-universe.read_structures.v1'
];

$authUrl = 'https://login.eveonline.com/v2/oauth/authorize?' . http_build_query([
    'response_type' => 'code',
    'redirect_uri' => $callbackUrl,
    'client_id' => $clientId,
    'scope' => implode(' ', $scopes),
    'state' => bin2hex(random_bytes(16))
]);

echo "1. Visit this URL in your browser:\n\n";
echo $authUrl . "\n\n";
echo "2. After authorizing, you'll be redirected to your callback URL.\n";
echo "3. Copy the 'code' parameter from the URL.\n";
echo "4. Enter the code here: ";

$code = trim(fgets(STDIN));

// Exchange code for tokens
$ch = curl_init('https://login.eveonline.com/v2/oauth/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code
]));
curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$data = json_decode($response, true);

if (isset($data['refresh_token'])) {
    echo "\n=== SUCCESS ===\n";
    echo "Refresh Token: " . $data['refresh_token'] . "\n";
    echo "Access Token: " . $data['access_token'] . "\n";
    echo "\nAdd this to your .env file:\n";
    echo "ESI_REFRESH_TOKEN=" . $data['refresh_token'] . "\n";
} else {
    echo "\n=== ERROR ===\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
```

Run: `php get_token.php`

#### Option B: Manual OAuth Flow

1. Build the authorization URL manually
2. Visit it in your browser and authorize
3. Get the authorization code from the callback URL
4. Exchange it for a refresh token using curl or Postman

### 4. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and fill in your values:

```bash
ESI_CLIENT_ID=your_client_id_here
ESI_CLIENT_SECRET=your_client_secret_here
ESI_REFRESH_TOKEN=your_refresh_token_here
ESI_CHARACTER_ID=your_character_id_here
```

**Note:** You can get your character ID from the access token JWT or by calling the verify endpoint.

## Running Tests

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Class

```bash
vendor/bin/phpunit tests/Integration/PublicEndpointsTest.php
vendor/bin/phpunit tests/Integration/AuthenticatedEndpointsTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter testGetServerStatus
vendor/bin/phpunit --filter testGetCharacterLocation
```

### Verbose Output

```bash
vendor/bin/phpunit --verbose
```

## Test Structure

```
tests/
├── bootstrap.php                      # PHPUnit bootstrap, loads env vars
├── Integration/
│   ├── BaseIntegrationTest.php        # Base class with token refresh logic
│   ├── PublicEndpointsTest.php        # Tests for public endpoints (no auth)
│   └── AuthenticatedEndpointsTest.php # Tests for authenticated endpoints
└── README.md                          # This file
```

## Test Coverage

### Public Endpoints (no authentication required)

- Server status
- Universe data (regions, systems, stations)
- Character/corporation/alliance public info
- Sovereignty map
- Route calculation
- And more...

### Authenticated Endpoints (require access token)

- Character location
- Character ship
- Character online status
- Character roles
- Character clones
- SSO verification
- Search (with structure access)
- And more...

## Troubleshooting

### "ESI credentials not configured"

Make sure you have created a `.env` file with all required values. The tests will be skipped if credentials are missing.

### "Failed to refresh access token"

Check that:
- Your Client ID and Client Secret are correct
- Your refresh token is valid (tokens can expire or be revoked)
- You have network connectivity to login.eveonline.com

### "ESI_CHARACTER_ID not configured"

Some tests require a character ID. Add your character ID to the `.env` file. You can find it by:
1. Calling the SSO verify endpoint with your access token
2. Decoding your JWT access token
3. Using an online character lookup tool

### Rate Limiting

ESI has rate limits. If you're hitting them:
- Add delays between test runs
- Reduce the number of tests running
- Check ESI status at https://esi.evetech.net/ui/

## Notes

- These are **integration tests** that hit real API endpoints
- Tests may fail if EVE servers are down or ESI is having issues
- Access tokens are automatically refreshed when they expire
- Tests can be run repeatedly - the refresh token is long-lived
- Be mindful of ESI rate limits when running tests frequently

## Contributing

When adding new endpoint tests:
1. Add the test method to the appropriate test class (public vs authenticated)
2. Use descriptive test names: `testGet<Endpoint>()`
3. Include assertions for expected response structure
4. Add helpful echo statements for debugging
5. Handle errors gracefully with `assertNoError()`
