<?php
require_once __DIR__ . '/ml_scraper.php';
$config = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Autenticação
require __DIR__ . '/auth.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($query === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro `q` é obrigatório.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = ml_search_items($query, $sort, $page, $config);

if ($result['error']) {
    // Se o erro for "Nenhum resultado", talvez seja melhor retornar 200 com items vazio,
    // mas se for erro de rede, 502.
    if (strpos($result['error'], 'Nenhum resultado') !== false) {
        echo json_encode(['items' => [], 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(502);
        echo json_encode(['error' => $result['error']], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(['items' => $result['items']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
