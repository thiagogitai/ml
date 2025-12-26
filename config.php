<?php
// Public config (safe to commit).
// Put your private cookies/overrides in `config.local.php` (ignored by git).

$config = [
    'ml_base_url' => 'https://lista.mercadolivre.com.br/',
    'ml_cookie' => '',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36',
    // Optional: Alibaba cookies to reduce `bxpunish`/captcha on some requests.
    'alibaba_cookie' => '',
    // Security: Token para acessar a API
    'api_token' => 'ml_secure_vps_8291_xPT',
];

$localPath = __DIR__ . '/config.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    if (is_array($local)) {
        $config = array_replace($config, $local);
    }
}

return $config;
