<?php

function app_base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $projectRoot = realpath(dirname(__DIR__));
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($projectRoot === false || $documentRoot === false) {
        return $basePath = '';
    }

    $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
    $documentRoot = rtrim($documentRoot, DIRECTORY_SEPARATOR);
    if (strcasecmp($projectRoot, $documentRoot) === 0) {
        return $basePath = '';
    }

    $documentRootPrefix = $documentRoot . DIRECTORY_SEPARATOR;
    if (stripos($projectRoot, $documentRootPrefix) !== 0) {
        return $basePath = '';
    }

    $relativePath = substr($projectRoot, strlen($documentRootPrefix));
    return $basePath = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
}

function app_url(string $path = ''): string
{
    return app_base_path() . '/' . ltrim($path, '/');
}
