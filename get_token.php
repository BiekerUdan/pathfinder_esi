<?php
/**
 * Helper script to get ESI refresh token via OAuth2 flow
 */

require 'vendor/autoload.php';

echo "=================================================================\n";
echo "EVE Online SSO - Get Refresh Token\n";
echo "=================================================================\n\n";

echo "Enter your Client ID: ";
$clientId = trim(fgets(STDIN));

echo "Enter your Client Secret: ";
$clientSecret = trim(fgets(STDIN));

echo "Enter your Callback URL (e.g., http://localhost/callback): ";
$callbackUrl = trim(fgets(STDIN));

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

$state = bin2hex(random_bytes(16));

$authUrl = 'https://login.eveonline.com/v2/oauth/authorize?' . http_build_query([
    'response_type' => 'code',
    'redirect_uri' => $callbackUrl,
    'client_id' => $clientId,
    'scope' => implode(' ', $scopes),
    'state' => $state
]);

echo "\n=================================================================\n";
echo "STEP 1: Authorize in Browser\n";
echo "=================================================================\n\n";
echo "Visit this URL in your browser:\n\n";
echo $authUrl . "\n\n";
echo "After authorizing, you'll be redirected to your callback URL.\n";
echo "The URL will contain a 'code' parameter.\n\n";

echo "=================================================================\n";
echo "STEP 2: Exchange Code for Token\n";
echo "=================================================================\n\n";
echo "Paste the authorization code here: ";
$code = trim(fgets(STDIN));

echo "\nExchanging code for tokens...\n";

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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

echo "\n=================================================================\n";
if ($httpCode === 200 && isset($data['refresh_token'])) {
    echo "SUCCESS!\n";
    echo "=================================================================\n\n";

    // Decode JWT to get character info
    $accessTokenParts = explode('.', $data['access_token']);
    if (count($accessTokenParts) === 3) {
        $payload = json_decode(base64_decode(strtr($accessTokenParts[1], '-_', '+/')), true);
        $characterId = $payload['sub'] ?? 'N/A';
        $characterName = $payload['name'] ?? 'N/A';

        // Extract character ID from sub (format: "CHARACTER:EVE:12345")
        if (preg_match('/CHARACTER:EVE:(\d+)/', $characterId, $matches)) {
            $characterId = $matches[1];
        }
    } else {
        $characterId = 'N/A';
        $characterName = 'N/A';
    }

    echo "Character: {$characterName}\n";
    echo "Character ID: {$characterId}\n\n";
    echo "Refresh Token: {$data['refresh_token']}\n";
    echo "Access Token: {$data['access_token']}\n";
    echo "Expires In: {$data['expires_in']} seconds\n\n";

    echo "=================================================================\n";
    echo "Add these to your .env file:\n";
    echo "=================================================================\n\n";
    echo "ESI_CLIENT_ID={$clientId}\n";
    echo "ESI_CLIENT_SECRET={$clientSecret}\n";
    echo "ESI_REFRESH_TOKEN={$data['refresh_token']}\n";
    echo "ESI_CHARACTER_ID={$characterId}\n";
    echo "\n";
} else {
    echo "ERROR\n";
    echo "=================================================================\n\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($data['error'])) {
        echo "Error: {$data['error']}\n";
        if (isset($data['error_description'])) {
            echo "Description: {$data['error_description']}\n";
        }
    }
}

echo "\n";
