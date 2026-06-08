<?php
// ── Google OAuth Configuration ────────────────────
// Isi CLIENT_ID dan CLIENT_SECRET dari Google Cloud Console
// https://console.cloud.google.com → APIs & Services → Credentials

define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://kostapik-singgahsini.infinityfreeapp.com/google-callback.php');

function getGoogleAuthUrl(): string {
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_state'] = $state;

    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'state'         => $state,
    ];
    return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
}

function getGoogleAccessToken(string $code): ?string {
    $url  = 'https://oauth2.googleapis.com/token';
    $data = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;
    $result = json_decode($response, true);
    return $result['access_token'] ?? null;
}

function getGoogleUserInfo(string $accessToken): ?array {
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;
    return json_decode($response, true);
}
