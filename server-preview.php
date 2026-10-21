<?php

/**
 * Base44 sandbox preview router.
 *
 * Serves the isolated admin-UI preview (public/preview/index.html) at "/" so the
 * dashboard redesign is visible in the Base44 preview without a signed-in session,
 * and passes every other request straight through to the Laravel application.
 *
 * This file is not part of the application: Laravel never loads it, and it is only
 * used because docker-compose.base44.yml starts the dev server with it. If the
 * preview file is absent, "/" falls through to Laravel exactly as before.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

$preview = __DIR__ . '/public/preview/index.html';

if (($uri === '/' || $uri === '') && is_file($preview)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($preview);

    return true;
}

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
