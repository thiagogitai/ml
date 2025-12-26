<?php
// Middleware de Autenticação Simples

// Se não houver configuração carregada, tenta carregar
if (!isset($config)) {
    $config = require __DIR__ . '/config.php';
}

$validToken = $config['api_token'] ?? null;

// Se não houver token configurado ou for o padrão dev, deixamos passar com aviso (ou bloqueia, dependendo da rigidez desejada).
// Aqui vamos bloquear se não bater.

if (empty($validToken)) {
    // Se não tiver token configurado, erro interno
    http_response_code(500);
    echo json_encode(['error' => 'API Token não configurado no servidor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tenta pegar o token do Header ou Query Params
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// Aceita "Bearer TOKEN" ou apenas "TOKEN"
$receivedToken = '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $receivedToken = trim($matches[1]);
} else {
    $receivedToken = trim($authHeader);
}

// Fallback: Tenta pegar da URL (?token=XYZ) para facilitar testes no navegador
if (empty($receivedToken) && isset($_GET['token'])) {
    $receivedToken = trim($_GET['token']);
}

// Verifica
if ($receivedToken !== $validToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Acesso não autorizado. Token inválido ou ausente.'], JSON_UNESCAPED_UNICODE);
    exit;
}
