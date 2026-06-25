<?php
declare(strict_types=1);

const RAG_DATA_FILE = __DIR__ . '/rag/data/docs.txt';
const RAG_INDEX_FILE = __DIR__ . '/rag/index.json';
const RAG_CACHE_DB = __DIR__ . '/rag/rag_cache.db';

function rag_load_env(): array {
    static $env = null;
    if ($env !== null) {
        return $env;
    }

    $env = [];
    $path = __DIR__ . '/.env';
    if (!is_file($path)) {
        return $env;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $env;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        $value = trim($value, "\"'");
        if ($key !== '') {
            $env[$key] = $value;
        }
    }

    return $env;
}

function rag_env(array $env, string $key, string $default = ''): string {
    return isset($env[$key]) && is_string($env[$key]) ? $env[$key] : $default;
}

function rag_config(): array {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $env = rag_load_env();
    $fileConfig = [];
    $configPath = __DIR__ . '/gigachat_config.php';
    if (is_file($configPath)) {
        $loaded = include $configPath;
        if (is_array($loaded)) {
            $fileConfig = $loaded;
        }
    }

    $basicAuth = (string)($fileConfig['basic_auth'] ?? rag_env($env, 'GIGACHAT_BASIC_AUTH'));
    if ($basicAuth === '') {
        $basicAuth = (string)($fileConfig['auth_key'] ?? rag_env($env, 'GIGACHAT_AUTH_KEY'));
    }
    if ($basicAuth === '') {
        $clientId = rag_env($env, 'CLIENT_ID');
        $clientSecret = rag_env($env, 'CLIENT_SECRET');
        if ($clientId !== '' && $clientSecret !== '') {
            $basicAuth = base64_encode($clientId . ':' . $clientSecret);
        }
    }
  if (str_starts_with(strtolower($basicAuth), 'basic ')) {
        $basicAuth = trim(substr($basicAuth, 6));
    }

    $rqUid = (string)($fileConfig['rq_uid'] ?? rag_env($env, 'GIGACHAT_RQUID', rag_env($env, 'CLIENT_ID')));
    if ($rqUid === '') {
        $rqUid = rag_make_uuid_v4();
    }

    $config = [
        'basic_auth' => $basicAuth,
        'rq_uid' => $rqUid,
        'scope' => (string)($fileConfig['scope'] ?? rag_env($env, 'GIGACHAT_SCOPE', 'GIGACHAT_API_PERS')),
        'chat_model' => (string)($fileConfig['model'] ?? rag_env($env, 'GIGACHAT_CHAT_MODEL', 'GigaChat')),
        'embeddings_model' => (string)($fileConfig['embeddings_model'] ?? rag_env($env, 'GIGACHAT_EMBEDDINGS_MODEL', 'Embeddings')),
        'oauth_url' => (string)($fileConfig['oauth_url'] ?? rag_env($env, 'GIGACHAT_OAUTH_URL', 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth')),
        'chat_url' => (string)($fileConfig['chat_url'] ?? rag_env($env, 'GIGACHAT_CHAT_URL', 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions')),
        'embeddings_url' => (string)($fileConfig['embeddings_url'] ?? rag_env($env, 'GIGACHAT_EMBEDDINGS_URL', 'https://gigachat.devices.sberbank.ru/api/v1/embeddings')),
        'verify_ssl' => filter_var(
            array_key_exists('verify_ssl', $fileConfig) ? $fileConfig['verify_ssl'] : rag_env($env, 'GIGACHAT_VERIFY', 'false'),
            FILTER_VALIDATE_BOOLEAN
        ),
        'top_k' => max(1, (int)rag_env($env, 'TOP_K', '3')),
    ];

    return $config;
}

function rag_make_uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    $hex = bin2hex($data);
    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

function rag_http_json(string $url, array $headers, ?string $body, bool $verifySsl, string $method = 'POST'): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $body ?? '';
    }
    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false) {
        return [false, ['error' => $curlError ?: 'cURL error', 'status' => 0]];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [false, ['error' => 'Invalid JSON response', 'status' => $status, 'raw' => $response]];
    }
    if ($status < 200 || $status >= 300) {
        return [false, ['error' => 'HTTP ' . $status, 'status' => $status, 'response' => $decoded]];
    }

    return [true, $decoded];
}

function rag_get_access_token(array $config): string {
    static $token = null;
    static $expiresAt = 0;
    if ($token !== null && time() < $expiresAt) {
        return $token;
    }

    if ($config['basic_auth'] === '') {
        throw new RuntimeException('Не настроены параметры GigaChat');
    }

    [$ok, $data] = rag_http_json(
        $config['oauth_url'],
        [
            'Authorization: Basic ' . $config['basic_auth'],
            'RqUID: ' . $config['rq_uid'],
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        http_build_query(['scope' => $config['scope']]),
        $config['verify_ssl']
    );

    if (!$ok) {
        throw new RuntimeException('Не удалось получить токен');
    }

    $accessToken = (string)($data['access_token'] ?? '');
    if ($accessToken === '') {
        throw new RuntimeException('Пустой access_token');
    }

    $token = $accessToken;
    $expiresAt = time() + 1700;
    return $token;
}

function rag_fallback_embedding(string $text): array {
    $hash = hash('sha256', $text, true);
    $vector = [];
    for ($i = 0; $i < 768; $i++) {
        $vector[] = (ord($hash[$i % strlen($hash)]) / 255.0) - 0.5;
    }
    return $vector;
}

function rag_get_embeddings(array $texts, array $config): array {
    $token = rag_get_access_token($config);
    $payload = json_encode([
        'model' => $config['embeddings_model'],
        'input' => array_values($texts),
    ], JSON_UNESCAPED_UNICODE);

    [$ok, $data] = rag_http_json(
        $config['embeddings_url'],
        [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        $payload,
        $config['verify_ssl']
    );

    if (!$ok) {
        $embeddings = [];
        foreach ($texts as $text) {
            $embeddings[] = rag_fallback_embedding((string)$text);
        }
        return $embeddings;
    }

    $items = $data['data'] ?? [];
    $embeddings = [];
    foreach ($items as $item) {
        $embeddings[] = $item['embedding'] ?? rag_fallback_embedding('');
    }
    return $embeddings;
}

function rag_chunk_text(string $text, int $chunkSize = 500, int $overlap = 100): array {
    $paragraphs = preg_split("/\n\s*\n/", trim($text)) ?: [];
    $chunks = [];
    $current = '';

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim((string)$paragraph);
        if ($paragraph === '') {
            continue;
        }

        if ($current !== '' && strlen($current) + strlen($paragraph) + 2 <= $chunkSize) {
            $current .= "\n\n" . $paragraph;
            continue;
        }

        if ($current !== '') {
            $chunks[] = $current;
            $tail = substr($current, max(0, strlen($current) - $overlap));
            $current = ($tail !== '' ? $tail . "\n\n" : '') . $paragraph;
            continue;
        }

        if (strlen($paragraph) > $chunkSize) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
            $buf = '';
            foreach ($sentences as $sentence) {
                if ($buf !== '' && strlen($buf) + strlen($sentence) + 1 <= $chunkSize) {
                    $buf .= ' ' . $sentence;
                } else {
                    if ($buf !== '') {
                        $chunks[] = $buf;
                    }
                    $buf = $sentence;
                }
            }
            $current = $buf;
            continue;
        }

        $current = $paragraph;
    }

    if ($current !== '') {
        $chunks[] = $current;
    }

    return array_values(array_filter($chunks, static fn(string $c): bool => strlen($c) >= 50));
}

function rag_cosine_similarity(array $a, array $b): float {
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;
    $len = min(count($a), count($b));
    for ($i = 0; $i < $len; $i++) {
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }
    if ($normA == 0.0 || $normB == 0.0) {
        return 0.0;
    }
    return $dot / (sqrt($normA) * sqrt($normB));
}

function rag_ensure_index(array $config): array {
    if (is_file(RAG_INDEX_FILE)) {
        $raw = file_get_contents(RAG_INDEX_FILE);
        $decoded = json_decode($raw ?: '[]', true);
        if (is_array($decoded) && !empty($decoded['chunks'])) {
            return $decoded;
        }
    }

    if (!is_file(RAG_DATA_FILE)) {
        throw new RuntimeException('Файл базы знаний не найден: rag/data/docs.txt');
    }

    @set_time_limit(300);
    $text = file_get_contents(RAG_DATA_FILE);
    $chunks = rag_chunk_text((string)$text);
    if ($chunks === []) {
        throw new RuntimeException('База знаний пуста');
    }

    $index = ['chunks' => []];
    foreach ($chunks as $i => $chunk) {
        $embedding = rag_get_embeddings([$chunk], $config)[0];
        $index['chunks'][] = [
            'id' => 'doc_' . $i,
            'text' => $chunk,
            'embedding' => $embedding,
        ];
    }

    if (!is_dir(dirname(RAG_INDEX_FILE))) {
        mkdir(dirname(RAG_INDEX_FILE), 0755, true);
    }
    file_put_contents(RAG_INDEX_FILE, json_encode($index, JSON_UNESCAPED_UNICODE));

    return $index;
}

function rag_search(string $query, array $config): array {
    $index = rag_ensure_index($config);
    $queryEmbedding = rag_get_embeddings([$query], $config)[0];
    $scored = [];

    foreach ($index['chunks'] as $chunk) {
        $score = rag_cosine_similarity($queryEmbedding, $chunk['embedding']);
        $scored[] = [
            'id' => $chunk['id'],
            'text' => $chunk['text'],
            'distance' => 1 - $score,
            'score' => $score,
        ];
    }

    usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    return array_slice($scored, 0, $config['top_k']);
}

function rag_cache_init(): void {
    $db = new SQLite3(RAG_CACHE_DB);
    $db->exec('CREATE TABLE IF NOT EXISTS cache (
        query_hash TEXT PRIMARY KEY,
        query TEXT NOT NULL,
        answer TEXT NOT NULL,
        context TEXT,
        created_at TEXT NOT NULL
    )');
    $db->close();
}

function rag_cache_hash(string $query): string {
    $normalized = preg_replace('/\s+/', ' ', mb_strtolower(trim($query))) ?? trim($query);
    return hash('sha256', $normalized);
}

function rag_cache_get(string $query): ?array {
    rag_cache_init();
    $hash = rag_cache_hash($query);
    $db = new SQLite3(RAG_CACHE_DB);
    $stmt = $db->prepare('SELECT query, answer, context, created_at FROM cache WHERE query_hash = :hash');
    $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;
    $db->close();

    if (!$row) {
        return null;
    }

    return [
        'query' => $row['query'],
        'answer' => $row['answer'],
        'context' => json_decode((string)$row['context'], true) ?: [],
        'created_at' => $row['created_at'],
        'from_cache' => true,
    ];
}

function rag_cache_set(string $query, string $answer, array $context): void {
    rag_cache_init();
    $hash = rag_cache_hash($query);
    $db = new SQLite3(RAG_CACHE_DB);
    $stmt = $db->prepare('INSERT OR REPLACE INTO cache (query_hash, query, answer, context, created_at) VALUES (:hash, :query, :answer, :context, :created_at)');
    $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':query', $query, SQLITE3_TEXT);
    $stmt->bindValue(':answer', $answer, SQLITE3_TEXT);
    $stmt->bindValue(':context', json_encode($context, JSON_UNESCAPED_UNICODE), SQLITE3_TEXT);
    $stmt->bindValue(':created_at', gmdate('c'), SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
}

function rag_create_prompt(string $query, array $contextDocs): string {
    $parts = [];
    foreach ($contextDocs as $i => $doc) {
        $parts[] = 'Документ ' . ($i + 1) . ":\n" . $doc['text'] . "\n";
    }
    $context = implode("\n", $parts);

    return "Ты - полезный AI ассистент. Ответь на вопрос пользователя на основе предоставленного контекста.\n\n"
        . "Контекст:\n{$context}\n\n"
        . "Вопрос: {$query}\n\n"
        . "Инструкции:\n"
        . "- Отвечай только на основе предоставленного контекста\n"
        . "- Если в контексте нет информации для ответа, скажи об этом\n"
        . "- Будь точным и кратким\n"
        . "- Отвечай на русском языке\n\n"
        . 'Ответ:';
}

function rag_chat_completion(string $prompt, array $config): string {
    $token = rag_get_access_token($config);
    $payload = json_encode([
        'model' => $config['chat_model'],
        'messages' => [
            ['role' => 'system', 'content' => 'Ты - полезный AI ассистент, который отвечает на вопросы на основе предоставленного контекста.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.3,
        'max_tokens' => 500,
    ], JSON_UNESCAPED_UNICODE);

    [$ok, $data] = rag_http_json(
        $config['chat_url'],
        [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        $payload,
        $config['verify_ssl']
    );

    if (!$ok) {
        throw new RuntimeException('Ошибка запроса к GigaChat');
    }

    $answer = trim((string)($data['choices'][0]['message']['content'] ?? ''));
    if ($answer === '') {
        throw new RuntimeException('Пустой ответ модели');
    }

    return $answer;
}

function rag_query(string $userQuery, bool $useCache = true): array {
    $config = rag_config();
    if ($config['basic_auth'] === '') {
        throw new RuntimeException('Не настроены параметры GigaChat в .env');
    }

    if ($useCache) {
        $cached = rag_cache_get($userQuery);
        if ($cached !== null) {
            return $cached;
        }
    }

    $contextDocs = rag_search($userQuery, $config);
    $prompt = rag_create_prompt($userQuery, $contextDocs);
    $answer = rag_chat_completion($prompt, $config);

    $contextForCache = array_map(static fn(array $doc): string => $doc['text'], $contextDocs);
    if ($useCache) {
        rag_cache_set($userQuery, $answer, $contextForCache);
    }

    return [
        'query' => $userQuery,
        'answer' => $answer,
        'from_cache' => false,
        'context_docs' => array_map(static fn(array $doc): array => [
            'id' => $doc['id'],
            'text' => $doc['text'],
            'distance' => $doc['distance'],
        ], $contextDocs),
        'model' => $config['chat_model'],
    ];
}
