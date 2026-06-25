<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/rag_lib.php';

function fail(int $code, string $message, array $details = []): void {
    http_response_code($code);
    $payload = ['ok' => false, 'error' => $message];
    if ($details) {
        $payload['details'] = $details;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed');
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    fail(400, 'Некорректный JSON');
}

$message = trim((string)($payload['message'] ?? $payload['query'] ?? ''));
if ($message === '') {
    fail(400, 'Пустой запрос');
}

$useCache = !array_key_exists('use_cache', $payload) || (bool)$payload['use_cache'];

try {
    $result = rag_query($message, $useCache);
    echo json_encode([
        'ok' => true,
        'answer' => $result['answer'],
        'from_cache' => (bool)($result['from_cache'] ?? false),
        'context_docs' => $result['context_docs'] ?? [],
        'model' => $result['model'] ?? null,
        'cached_at' => $result['created_at'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    fail(502, $e->getMessage());
}
