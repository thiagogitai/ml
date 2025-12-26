<?php
// Example private config. Copy to `config.local.php` (which is ignored by git).
return [
    // Paste your Mercado Livre session cookies to improve scraping consistency.
    // 'ml_cookie' => 'cookie1=value1; cookie2=value2;',

    // Optional: change the UA if needed.
    // 'user_agent' => 'Mozilla/5.0 ...',

    // Optional: Alibaba cookies to reduce `bxpunish`/captcha on some requests.
    // 'alibaba_cookie' => 'key=value; key2=value2;',

    // Optional: proxy for Mercado Livre scraping (recommended on VPS due to account-verification redirects).
    // Supported proxy types: http, https, socks5, socks5h
    //
    // Example:
    // 'ml_proxy' => [
    //   'type' => 'http',
    //   'host' => '1.2.3.4',
    //   'port' => 3128,
    //   'username' => 'user', // optional
    //   'password' => 'pass', // optional
    // ],
];
