<?php
declare(strict_types=1);

const CTF_CSRF_SESSION_KEY = 'ctf_csrf_token';

function startCtfSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function getCtfCsrfToken(): string
{
    startCtfSession();

    $token = $_SESSION[CTF_CSRF_SESSION_KEY] ?? null;
    if (is_string($token) && $token !== '') {
        return $token;
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION[CTF_CSRF_SESSION_KEY] = $token;

    return $token;
}

function validateCtfCsrfToken(): bool
{
    startCtfSession();

    $sessionToken = $_SESSION[CTF_CSRF_SESSION_KEY] ?? null;
    $requestToken = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

    return is_string($sessionToken)
        && $sessionToken !== ''
        && $requestToken !== ''
        && hash_equals($sessionToken, $requestToken);
}