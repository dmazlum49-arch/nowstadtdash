<?php

/**
 * Base44 sandbox preview router.
 *
 * The preview's entry point is "/". This app's own "/" redirects to the
 * business-user login (/login), which rejects admin accounts, so the sandbox
 * sends "/" to the admin panel login (/panel/login) instead: the preview then
 * opens the panel this dashboard actually exposes and every page behind it is
 * reachable after signing in. Every other request is passed straight through to
 * the Laravel application, unchanged.
 *
 * This file is not part of the application: Laravel never loads it, and it is
 * only used because docker-compose.base44.yml starts the dev server with it.
 * Delete it and start the server with server.php to drop the redirect.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

if ($uri === '/' || $uri === '') {
    header('Location: /panel/login', true, 302);

    return true;
}

if (file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
