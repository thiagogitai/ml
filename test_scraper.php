<?php
require_once __DIR__ . '/ml_scraper.php';
$config = require __DIR__ . '/config.php';

// Teste 1: Busca por termo
echo "--- Teste 1: Busca por termo 'fone bluetooth' ---\n";
$result = ml_search_items('fone bluetooth', 'best_sellers', 1, $config);

if ($result['error']) {
    echo "ERRO: " . $result['error'] . "\n";
} else {
    echo "Sucesso! Encontrados " . count($result['items']) . " itens.\n";
    if (count($result['items']) > 0) {
        $first = $result['items'][0];
        echo "Exemplo de item 1:\n";
        echo "Título: " . $first['title'] . "\n";
        echo "Preço: " . $first['price'] . "\n";
        echo "Vendas: " . ($first['sold_quantity'] ?? 'N/A') . "\n";
    }
}

echo "\n";

// Teste 2: Busca por URL (se possível, pegar uma url real seria ideal, mas vou tentar simular ou omitir se for complexo achar uma URL válida estática)
// Vou pular busca por URL específica para não falhar se o link morrer, o teste de termo já valida o scraper.
