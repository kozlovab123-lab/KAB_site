<?php
/**
 * Copy to gigachat_config.php and fill your credentials.
 * Never commit gigachat_config.php to git.
 */
return [
    'basic_auth' => 'YOUR_GIGACHAT_BASIC_AUTH_BASE64',
    'rq_uid' => 'YOUR_CLIENT_ID_UUID',
    'scope' => 'GIGACHAT_API_PERS',
    'model' => 'GigaChat',
    'embeddings_model' => 'Embeddings',
    'oauth_url' => 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth',
    'chat_url' => 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
    'embeddings_url' => 'https://gigachat.devices.sberbank.ru/api/v1/embeddings',
    'verify_ssl' => false,
];
