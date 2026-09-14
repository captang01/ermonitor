<?php

/**
 * ERMonitor application helpers.
 * The base path is detected from the Apache document root so the app can be
 * copied to a different folder without hard-coded /ermonitor redirects.
 */
function ermonitorBasePath(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? realpath($_SERVER['DOCUMENT_ROOT'])
        : false;
    $appRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

    if ($documentRoot && $appRoot) {
        $doc = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $app = str_replace('\\', '/', $appRoot);
        if (str_starts_with($app, $doc)) {
            $relative = substr($app, strlen($doc));
            $basePath = '/' . trim($relative, '/');
            if ($basePath === '/') {
                $basePath = '';
            }
            return $basePath;
        }
    }

    return '/ermonitor';
}

function ermonitorUrl(string $path = ''): string
{
    return rtrim(ermonitorBasePath(), '/') . '/' . ltrim($path, '/');
}
