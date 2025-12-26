# Documentação da API

O sistema foi refatorado para expor endpoints RESTful para consulta de produtos no Mercado Livre e busca por imagem no Alibaba.

## Endpoints

### 1. Buscar Produtos no Mercado Livre

**Endpoint:** `GET /api_search.php`

**Parâmetros:**
- `q` (obrigatório): Termo de busca ou link do Mercado Livre.
- `page` (opcional): Número da página (padrão: 1).
- `sort` (opcional): Ordenação (`price_asc`, `best_sellers`).

**Exemplo de Requisição:**
```
GET /api_search.php?q=iphone&sort=best_sellers
```

**Exemplo de Resposta (JSON):**
```json
{
  "items": [
    {
      "title": "Apple iPhone 13 (128 Gb) - Meia-noite",
      "link": "https://www.mercadolivre.com.br/...",
      "img": "https://http2.mlstatic.com/...",
      "price": "R$ 3.800,00",
      "price_value": 3800.00,
      "sold_quantity": 15000,
      "rating_value": 4.8,
      ...
    }
  ]
}
```

### 2. Buscar Fornecedores no Alibaba (por imagem)

**Endpoint:** `GET /alibaba_image_search.php`

**Parâmetros:**
- `imagePath` (obrigatório): URL da imagem do produto (ex: a URL da imagem retornada pelo endpoint de busca do ML).
- `pageSize` (opcional): Quantidade de resultados.

**Exemplo de Requisição:**
```
GET /alibaba_image_search.php?imagePath=https://http2.mlstatic.com/...
```

**Exemplo de Resposta (JSON):**
```json
{
  "ok": true,
  "data": { ... } // Resultados brutos do Alibaba
}
```

## Bibliotecas

- `ml_scraper.php`: Contém toda a lógica de scraping e análise de HTML do Mercado Livre. Pode ser reutilizada em outros scripts PHP via `require 'ml_scraper.php'`.
