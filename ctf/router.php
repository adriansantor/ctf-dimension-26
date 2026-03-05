<?php
declare(strict_types=1);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';

if (preg_match('#^/(private|logs)(/|$)#', $uriPath) === 1 || $uriPath === '/retos.csv') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden";
    return true;
}

if (is_file(__DIR__ . $uriPath)) {
    return false;
}

require __DIR__ . '/backend.php';
return true;
