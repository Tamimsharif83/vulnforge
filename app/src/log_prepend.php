<?php
// Loaded by PHP's auto_prepend_file before every web request, so the access log
// captures all PHP execution — routed pages and directly-executed uploaded
// files alike. CLI (tests, tooling) is skipped. Fully fail-safe.
if (PHP_SAPI === 'cli') {
    return;
}
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/AccessLog.php';
require_once __DIR__ . '/AppEvents.php';
try {
    AccessLog::begin();
    AppEvents::registerErrorShutdown();
} catch (\Throwable $e) {
    // Logging must never break a request.
}
