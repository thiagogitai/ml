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

function extract_icbuimgsearch_path($value) {
    if (is_string($value)) {
        if (strpos($value, '/icbuimgsearch/') === 0) {
            return $value;
        }
        if (preg_match('~^https?://icbu-picture\\.oss-[^/]+\\.aliyuncs\\.com(/icbuimgsearch/[^?]+)~i', $value, $m)) {
            return $m[1];
        }
    }
    if (is_array($value)) {
        foreach ($value as $v) {
            $found = extract_icbuimgsearch_path($v);
            if ($found) {
                return $found;
            }
        }
    }
    return null;
}

function normalize_image_address($value) {
    if (!is_string($value) || $value === '') {
        return null;
    }
    $path = $value;
    $pos = strpos($path, '@@');
    if ($pos !== false) {
        $path = substr($path, 0, $pos);
    }
    if (strpos($path, '/icbuimgsearch/') !== 0) {
        return null;
    }
    return $path;
}

function oss_host_from_store_tag($tag) {
    $t = strtolower(trim((string)$tag));
    if ($t === 'oss_us') {
        return 'https://icbu-picture.oss-us-west-1.aliyuncs.com';
    }
    return null;
}

function download_image_as_data_uri($url, $userAgent) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => $userAgent ?: 'Mozilla/5.0',
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => [
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        ],
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $body === null || $body === '') {
        return [null, "Falha ao baixar imagem ($status): $err"];
    }
    if ($status < 200 || $status >= 300) {
        return [null, "Falha ao baixar imagem (HTTP $status)."];
    }

    $mime = 'image/jpeg';
    if (is_string($ct) && $ct !== '') {
        $mime = trim(explode(';', $ct)[0]);
    }
    $b64 = base64_encode($body);
    return ["data:$mime;base64,$b64", null];
}

// Accept either:
// - picUrl: data:image/...;base64,...
// - imageUrl: URL da imagem (será baixada e convertida em data URI)
$picUrl = isset($_POST['picUrl']) ? trim((string)$_POST['picUrl']) : '';
$imageUrl = isset($_POST['imageUrl']) ? trim((string)$_POST['imageUrl']) : '';

if ($picUrl === '' && $imageUrl === '') {
    respond(400, ['ok' => false, 'error' => 'Envie `picUrl` (data URI base64) ou `imageUrl` (URL da imagem).']);
}

if ($picUrl === '' && $imageUrl !== '') {
    [$picUrl, $err] = download_image_as_data_uri($imageUrl, $config['user_agent'] ?? 'Mozilla/5.0');
    if ($err) {
        respond(502, ['ok' => false, 'error' => $err]);
    }
}

if (strpos($picUrl, 'data:image/') !== 0 || strpos($picUrl, ';base64,') === false) {
    respond(400, ['ok' => false, 'error' => '`picUrl` precisa ser `data:image/...;base64,...`.']);
}

$apiUrl = 'https://open-s.alibaba.com/openservice/appImageSearchPicUrlUtilsService';
$refererUrl = 'https://www.alibaba.com/';

// The user observed this payload shape: picUrl=data:image/jpeg;base64,...
// Other fields are optional, but we include the ones seen in the browser flow to improve compatibility.
$postFields = [
    'picUrl' => $picUrl,
    'language' => isset($_POST['language']) ? (string)$_POST['language'] : 'pt',
    'uploadType' => isset($_POST['uploadType']) ? (string)$_POST['uploadType'] : 'uploadBtn',
    'sourceFrom' => isset($_POST['sourceFrom']) ? (string)$_POST['sourceFrom'] : 'imageupload',
];

$payload = http_build_query($postFields, '', '&', PHP_QUERY_RFC3986);

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
        'Content-Type: application/x-www-form-urlencoded',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 45,
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

$rawPath = extract_icbuimgsearch_path($json);
$imageAddress = normalize_image_address($rawPath);

$ossUrl = null;
$storeTag = null;
if (isset($json['data']) && is_array($json['data'])) {
    $storeTag = $json['data']['storeTag'] ?? null;
}
$host = $storeTag ? oss_host_from_store_tag($storeTag) : null;
if ($host && $imageAddress) {
    $ossUrl = $host . $imageAddress;
}

respond(200, [
    'ok' => true,
    'imageAddress' => $imageAddress,
    'rawImagePath' => $rawPath,
    'ossUrl' => $ossUrl,
    'data' => $json,
]);
