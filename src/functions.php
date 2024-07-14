<?php
namespace TBank;
/**
 * @param string $key
 * @return ?string
 */
function getEnv(string $key): ?string {
    if (!isset($_ENV[$key])) {
        return null;
    }
    if (preg_match('/^"(.*)"$/i', $_ENV[$key], $matches)) {
        return $matches[1];
    }
    return $_ENV[$key];
}

/**
 * Debug function
 * @param mixed $mixed
 * @param bool $exit
 * @return void
 */
function dbg(mixed $mixed, bool $exit = true): void {
    if (php_sapi_name() == 'cli') {
        echo json_encode($mixed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo '<pre>' . print_r($mixed, true) . '</pre>';
    }
    if ($exit) {
        exit();
    }
}
