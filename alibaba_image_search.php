<?php
$config = require __DIR__ . '/config.php';


header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Autenticação
require __DIR__ . '/auth.php';

function respond($status, $payload) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Inputs:
// - imagePath: URL da imagem (Mercado Livre, Alibaba, etc)
// - imageAddress: caminho OSS do Alibaba (ex: /icbuimgsearch/imageBase64_....jpeg)
$imagePath = isset($_GET['imagePath']) ? trim((string)$_GET['imagePath']) : '';
$imageAddressParam = isset($_GET['imageAddress']) ? trim((string)$_GET['imageAddress']) : '';

$region = isset($_GET['region']) ? trim((string)$_GET['region']) : '';
$pageSize = isset($_GET['pageSize']) ? max(1, min(50, (int)$_GET['pageSize'])) : 20;
$beginPage = isset($_GET['beginPage']) ? max(1, (int)$_GET['beginPage']) : 1;
$language = isset($_GET['language']) ? trim((string)$_GET['language']) : 'pt';

if ($imagePath === '' && $imageAddressParam === '') {
    respond(400, ['ok' => false, 'error' => 'Informe `imagePath` (URL) ou `imageAddress` (/icbuimgsearch/...).']);
}

$imageType = null;
$imageAddress = null;

if ($imageAddressParam !== '') {
    if (strpos($imageAddressParam, '/icbuimgsearch/') !== 0) {
        respond(400, ['ok' => false, 'error' => '`imageAddress` deve começar com `/icbuimgsearch/`.']);
    }
    $imageType = 'oss';
    $imageAddress = $imageAddressParam;
} else {
    if (filter_var($imagePath, FILTER_VALIDATE_URL) === false) {
        respond(400, ['ok' => false, 'error' => 'Parâmetro `imagePath` inválido (precisa ser URL).']);
    }
    if (preg_match('~^https?://icbu-picture\\.oss-[^/]+\\.aliyuncs\\.com(/icbuimgsearch/[^?]+)~i', $imagePath, $m)) {
        $imageType = 'oss';
        $imageAddress = $m[1];
    } else {
        $imageType = 'url';
        $imageAddress = $imagePath;
    }
}

$params = [
    'pageSize' => (string)$pageSize,
    'beginPage' => (string)$beginPage,
    'imageType' => $imageType,
    'imageAddress' => $imageAddress,
    'categoryId' => '66666666',
    'language' => ($language !== '' ? $language : 'pt'),
];
if ($region !== '') {
    $params['region'] = $region;
}

$apiUrl = 'https://open-s.alibaba.com/openservice/imageSearchViewService?' . http_build_query($params);
$refererUrl = 'https://www.alibaba.com/';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => $config['user_agent'] ?? 'Mozilla/5.0',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'Origin: https://www.alibaba.com',
        'Referer: ' . $refererUrl,
    ],
    CURLOPT_TIMEOUT => 25,
]);
$cookie = $config['alibaba_cookie'] ?? '';
if ($cookie !== '') {
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
}

$body = curl_exec($ch);
$curlErr = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($body === false) {
    respond(502, ['ok' => false, 'error' => 'Falha ao consultar Alibaba: ' . $curlErr]);
}

$json = json_decode($body, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    respond(502, [
        'ok' => false,
        'error' => 'Resposta do Alibaba não é JSON.',
        'http_status' => $status,
        'content_type' => $contentType,
    ]);
}

respond(200, ['ok' => true, 'data' => $json]);

