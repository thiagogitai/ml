<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond($status, $payload) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function bcb_ptax_url_for_date($dateMdY) {
    // Example: 12-24-2025
    $base = "https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoDolarDia(dataCotacao=@dataCotacao)";
    return $base . "?@dataCotacao='" . rawurlencode($dateMdY) . "'&$format=json";
}

function http_get_json($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ],
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return [null, $status, $err ?: 'curl error'];
    }
    $json = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, $status, 'json parse error'];
    }
    return [$json, $status, null];
}

function cache_path() {
    return __DIR__ . '/_cache_fx_usdbrl.json';
}

$today = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));

// Simple daily cache (avoid multiple BCB calls per page load).
$cacheFile = cache_path();
if (is_file($cacheFile)) {
    $raw = @file_get_contents($cacheFile);
    if (is_string($raw) && $raw !== '') {
        $cached = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($cached)) {
            $cacheDate = $cached['date'] ?? null;
            $rate = $cached['rate'] ?? null;
            if (is_string($cacheDate) && is_numeric($rate) && $cacheDate === $today->format('Y-m-d')) {
                respond(200, [
                    'ok' => true,
                    'source' => 'bcb_ptax_cache',
                    'date' => $cached['ptax_date'] ?? $cacheDate,
                    'datetime' => $cached['datetime'] ?? null,
                    'usdbrl_sell' => (float)$rate,
                ]);
            }
        }
    }
}

// Try today, then walk back up to 10 days (weekends/holidays).
$found = null;
$ptaxDate = null;
$ptaxDateTime = null;
for ($i = 0; $i < 10; $i++) {
    $d = $today->sub(new DateInterval('P' . $i . 'D'));
    $mdy = $d->format('m-d-Y');
    [$json, $status, $err] = http_get_json(bcb_ptax_url_for_date($mdy));
    if (!$json || !isset($json['value']) || !is_array($json['value']) || !isset($json['value'][0])) {
        continue;
    }
    $row = $json['value'][0];
    if (!isset($row['cotacaoVenda']) || !is_numeric($row['cotacaoVenda'])) {
        continue;
    }
    $found = (float)$row['cotacaoVenda'];
    $ptaxDateTime = $row['dataHoraCotacao'] ?? null;
    $ptaxDate = $d->format('Y-m-d');
    break;
}

if ($found === null) {
    respond(502, ['ok' => false, 'error' => 'Não foi possível obter a cotação PTAX do Banco Central.']);
}

@file_put_contents($cacheFile, json_encode([
    'date' => $today->format('Y-m-d'),
    'ptax_date' => $ptaxDate,
    'datetime' => $ptaxDateTime,
    'rate' => $found,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

respond(200, [
    'ok' => true,
    'source' => 'bcb_ptax',
    'date' => $ptaxDate,
    'datetime' => $ptaxDateTime,
    'usdbrl_sell' => $found,
]);

