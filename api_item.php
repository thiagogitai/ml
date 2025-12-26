<?php
require_once __DIR__ . '/ml_scraper.php';
$config = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Autenticação
require __DIR__ . '/auth.php';

$query = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro `id` (ID do item ou URL) é obrigatório.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tenta extrair ID se for URL
$itemId = $query;
if (filter_var($query, FILTER_VALIDATE_URL)) {
    // Tenta extrair MLB... do link
    if (preg_match('/(MLB\d+)/i', $query, $matches)) {
        $itemId = strtoupper($matches[1]);
    } else {
        // Fallback: tenta extrair ID de produto (/p/MLB...)
        $itemId = ml_extract_product_id_from_url($query);
    }
}

if (empty($itemId) || !preg_match('/^MLB\d+$/i', $itemId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de item inválido. Use o formato MLB123456789 ou uma URL válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$res = ml_get_item_metrics($itemId, $config);

if (!$res['ok']) {
    http_response_code(502);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// Para o teste inicial e mapeamento detalhado, vamos retornar tudo por enquanto
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
