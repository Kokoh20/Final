<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// Allow same-origin XHR. If you need CORS, uncomment below:
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Content-Type');

function send_json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function read_json(string $path, array $fallback = []): array {
    if (!file_exists($path)) {
        return $fallback;
    }
    $json = file_get_contents($path);
    if ($json === false || $json === '') {
        return $fallback;
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : $fallback;
}

function write_json_atomic(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
    }
    $tmp = tempnam($dir, 'json_');
    if ($tmp === false) {
        return false;
    }
    $ok = file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($ok === false) {
        @unlink($tmp);
        return false;
    }
    return rename($tmp, $path);
}

