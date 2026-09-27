<?php

declare(strict_types=1);

$baseUrl = rtrim((string) ($_SERVER['argv'][1] ?? getenv('IC_SMOKE_BASE_URL') ?: getenv('IC_TEST_BASE_URL') ?: 'http://localhost:8080'), '/');
$paths = array_filter(array_map('trim', explode(',', (string) (getenv('IC_SMOKE_PATHS') ?: '/,/eventi-cosplay,/eventi-cosplay-weekend,/eventi-cosplay-mese,/blog,/login,/register'))));
$timeout = (int) (getenv('IC_SMOKE_TIMEOUT') ?: 10);

if ($baseUrl === '') {
    fwrite(STDERR, "Missing smoke base URL.\n");
    exit(1);
}

echo "Smoke base URL: {$baseUrl}\n";

foreach ($paths as $path) {
    $url = $baseUrl . '/' . ltrim($path, '/');
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true,
            'header' => "User-Agent: ItalianCosplaySmokeTest/1.0\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $status = extractStatusCode($http_response_header ?? []);

    if ($body === false || $status < 200 || $status >= 400 || trim($body) === '') {
        fwrite(STDERR, "Smoke failed for {$url} (HTTP {$status}).\n");
        exit(1);
    }

    if (stripos($body, 'Fatal error') !== false || stripos($body, 'Uncaught') !== false) {
        fwrite(STDERR, "Smoke found a PHP error marker in {$url}.\n");
        exit(1);
    }

    echo sprintf("%-28s OK (HTTP %d)\n", $path, $status);
}

echo "Smoke tests ........... OK\n";

function extractStatusCode(array $headers): int
{
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
            return (int) $matches[1];
        }
    }

    return 0;
}
