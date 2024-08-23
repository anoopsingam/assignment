<?php

require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Function to convert JWK to PEM format
function jwkToPem($jwk) {
    $n = base64_url_decode($jwk['n']);
    $e = base64_url_decode($jwk['e']);

    $modulus = new \phpseclib3\Math\BigInteger($n, 256);
    $exponent = new \phpseclib3\Math\BigInteger($e, 256);

    $rsa = \phpseclib3\Crypt\PublicKeyLoader::load([
        'n' => $modulus,
        'e' => $exponent,
    ]);

    return $rsa->toString('PKCS8');
}

// Function to decode base64url encoded strings
function base64_url_decode($data) {
    $data = strtr($data, '-_', '+/');
    $data = base64_decode($data);
    return $data;
}

// Example JWT Token
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJpYXQiOjE3MjQyNDEzNjIsImV4cCI6MTcyNDI0NDk2Miwicm9sZXMiOlsiUk9MRV9VU0VSIiwiUk9MRV9DT01QQU5ZX0FETUlOIl0sImVtYWlsIjoiYW5vb3ByYXRob2Q3QGdtYWlsLmNvbSJ9.NpgHIx0koCaJxh5uHwi9tg77VXuskwJq5uJ20d1IiXeZs7or69i8U0DrPEtN2kM6uvGkiN0fqTXb8ZeBq44Pc7oo423GuPibHgnzJaUImbdlUJLHHvg9NaCGZ8F_2GpDKphu1TUqoK1Ag28_b7PjgsKloSjkX96RNPAKYNuFQrqQfNn85dFSo4a-EYwMoOQAzbtRXysArjiImapvLQVl6H4qiYBf7Q2JdL65pl0n8zB-dfDxgJ9qz2LIui_t8Qy0EEX2hGk7HcwFu32J2CTL9q7rXmzuS4JtrMGEO2eBjHS5qXmk-79s8mYJ9XmEGx4eNOHApmNwy2J8Tnki9czAbOt_wWGDNRB9tBN5Ixbaz8rSopMP9tcI8ueQZ139-kOiqkbfM62kHWO4_zA8x7yJF9CrcWoRBcvXm30ViRZXZAdMRL0mDqOtkirWa69vmSTgEFpzKLhQjDyOWbYTRk-7YUv-uczCymSERiAfeJKscLu7ForWj6TgYququ9qHqClNctM9lprDWM39q3ohGXzlT78-8AsYCunRrz8ytBpox4thXV2bt9r-EsTf7rWPsKzTQcJe6QsmzoT9MaKqAxcRWfptvFjrwfoFBTWO1kE5kJR8fjdf7cEButXdAMhhNgD3p7n0IzRBy4Lqa5v-YDYkHLPijQjMASQUK0085WHYGO0'; // Replace with your JWT token

// Example JWK (Public Key) - Replace with your actual JWK
$jwk = [
    "kty" => "RSA",
    "alg" => "RS256",
    "use" => "sig",
    "kid" => "1",
    "n"   => "z8YYdDiHztyThgpBsv7F69dkTx6GHssZXSImJVNoXaL3WxIpiU_g9jecSTcjeAn_ffQ1QHxSkDVnfTPseXwW6VnGVeexfjUo0580L2UxWAOGq6BMVgmuyrQ8-bfax-8dED8dmwwQ2tRSzv1qgnTeZi2pvsY5_e_J7YwORX-9muZ3t1YNVZX5zOoLwmbC5DoAdQIqBxemIOX6_OqGEuaEEpKwU7qOZy2gJSSOYCRgKzW_zvITXMj-wDcqVo33BqXeGAE7giYRQ9H21zhALuPV9O1M_jEppfhkkcD5JomyRew_npwDxK40uj4dDh2VxdsqqhE0l8DncZRGmlS1bLZn9w",
    "e"   => "AQAB"
];

// Convert JWK to PEM format
$publicKey = jwkToPem($jwk);

try {
    // Decode JWT Token
    $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

    echo "Decoded JWT:\n";
    print_r($decoded);

} catch (Exception $e) {
    echo 'Token is invalid: ' . $e->getMessage();
}
