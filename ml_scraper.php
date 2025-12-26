<?php

function ml_is_url($value) {
    return filter_var($value, FILTER_VALIDATE_URL) !== false;
}

function ml_mb_strlen($s) {
    if (function_exists('mb_strlen')) {
        return mb_strlen($s, 'UTF-8');
    }
    return strlen($s);
}

function ml_mb_strtolower($s) {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($s, 'UTF-8');
    }
    return strtolower($s);
}

function ml_parse_price_brl($priceText) {
    if ($priceText === null) {
        return null;
    }
    $normalized = preg_replace('/[^0-9,\\.]/', '', (string)$priceText);
    if ($normalized === '') {
        return null;
    }
    if (strpos($normalized, ',') !== false && strpos($normalized, '.') !== false) {
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
    } elseif (strpos($normalized, ',') !== false) {
        $normalized = str_replace(',', '.', $normalized);
    }
    $value = (float)$normalized;
    return $value > 0 ? $value : null;
}

function ml_format_brl($value) {
    if ($value === null) {
        return null;
    }
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function ml_normalize_discount_label($text) {
    $t = trim((string)$text);
    if ($t === '') {
        return null;
    }
    if (preg_match('/(\\d{1,3}%\\s*OFF)/i', $t, $m)) {
        return strtoupper(str_replace('  ', ' ', trim($m[1])));
    }
    return $t;
}

function ml_parse_sold_quantity($text) {
    if ($text === null) {
        return null;
    }
    $t = ml_mb_strtolower(trim((string)$text));
    if ($t === '') {
        return null;
    }
    $t = str_replace(['|', '+', "\u{00A0}"], [' ', '', ' '], $t);
    $t = preg_replace('/\s+/', ' ', $t);

    if (preg_match('/mais de\s+(\d+)\s+produtos?\s+vendidos?/', $t, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/(\d+)\s+produtos?\s+vendidos?/', $t, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/(\d+)\s*mil\s+vendidos?/', $t, $m)) {
        return (int)$m[1] * 1000;
    }
    if (preg_match('/(\d+)\s*mil/', $t, $m)) {
        return (int)$m[1] * 1000;
    }
    if (preg_match('/(\d+)\s*vendidos?/', $t, $m)) {
        return (int)$m[1];
    }
    return null;
}

function ml_extract_product_id_from_url($url) {
    if (!$url) {
        return null;
    }
    if (preg_match('~/p/(MLB\\d+)~', $url, $m)) {
        return $m[1];
    }
    return null;
}

function ml_product_url_from_id($productId) {
    if (!$productId) {
        return null;
    }
    return 'https://www.mercadolivre.com.br/p/' . $productId;
}

function ml_build_click_url($meta) {
    if (!is_array($meta)) {
        return null;
    }
    $hostPath = $meta['url'] ?? null;
    if (!$hostPath || !is_string($hostPath)) {
        return null;
    }
    $hostPath = ltrim($hostPath, '/');
    $url = 'https://' . $hostPath;
    if (!empty($meta['url_params']) && is_string($meta['url_params'])) {
        $url .= $meta['url_params'];
    }
    if (!empty($meta['url_fragments']) && is_string($meta['url_fragments'])) {
        $url .= $meta['url_fragments'];
    }
    return $url;
}

function ml_collect_products($data, &$products) {
    if (is_array($data)) {
        $isAssoc = array_keys($data) !== range(0, count($data) - 1);
        if ($isAssoc) {
            if (isset($data['@type']) && $data['@type'] === 'Product') {
                $products[] = $data;
            }
            foreach ($data as $value) {
                ml_collect_products($value, $products);
            }
        } else {
            foreach ($data as $value) {
                ml_collect_products($value, $products);
            }
        }
    }
}

function ml_normalize_product($product) {
    $title = isset($product['name']) ? trim($product['name']) : null;
    $link = null;
    $img = null;
    $price = null;
    $soldQuantity = null;
    $ratingCount = null;
    $ratingValue = null;
    $productId = null;

    if (isset($product['offers'])) {
        $offers = $product['offers'];
        if (is_array($offers) && isset($offers[0])) {
            $offers = $offers[0];
        }
        if (is_array($offers)) {
            if (isset($offers['url'])) {
                $link = $offers['url'];
            }
            if (isset($offers['price'])) {
                $price = (string)$offers['price'];
            }
        }
    }
    if (!$link && isset($product['url'])) {
        $link = $product['url'];
    }
    $productId = ml_extract_product_id_from_url($link);
    if (isset($product['image'])) {
        $img = is_array($product['image']) ? $product['image'][0] : $product['image'];
    }
    if (isset($product['aggregateRating']) && is_array($product['aggregateRating'])) {
        if (isset($product['aggregateRating']['ratingCount'])) {
            $ratingCount = (int)$product['aggregateRating']['ratingCount'];
        }
        if (isset($product['aggregateRating']['ratingValue'])) {
            $ratingValue = (float)$product['aggregateRating']['ratingValue'];
        }
    }

    if ($title && $link) {
        $priceValue = is_numeric($price) ? (float)$price : ml_parse_price_brl($price);
        return [
            'title' => $title,
            'link' => $link,
            'img' => $img,
            'price' => $price,
            'price_value' => $priceValue,
            'sold_quantity' => $soldQuantity,
            'rating_count' => $ratingCount,
            'rating_value' => $ratingValue,
            'product_id' => $productId,
            'recommended' => null,
        ];
    }
    return null;
}

function ml_fetch_html($url, $userAgent, $cookie, $debug, $proxy = null) {
    $ch = curl_init();

    if (!empty($proxy) && is_array($proxy)) {
        $p = $proxy;
        if (!empty($p['host']) && !empty($p['port'])) {
            curl_setopt($ch, CURLOPT_PROXY, $p['host'] . ':' . $p['port']);

            $type = strtolower((string)($p['type'] ?? 'http'));
            $proxyType = CURLPROXY_HTTP;
            if ($type === 'https') $proxyType = CURLPROXY_HTTPS;
            if ($type === 'socks5') $proxyType = CURLPROXY_SOCKS5;
            if ($type === 'socks5h') $proxyType = CURLPROXY_SOCKS5_HOSTNAME;
            curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType);

            if (!empty($p['username']) || !empty($p['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, (string)($p['username'] ?? '') . ':' . (string)($p['password'] ?? ''));
            }
        }
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_ENCODING => 'gzip,deflate',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding: gzip, deflate',
        ],
        CURLOPT_COOKIE => $cookie,
        CURLOPT_TIMEOUT => 20,
    ]);
    $html = curl_exec($ch);
    $curlErr = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$html, $status, $curlErr];
}

function ml_extract_preloaded_state($html) {
    $needle = 'id="__PRELOADED_STATE__"';
    $pos = strpos($html, $needle);
    if ($pos === false) {
        $needle = "id='__PRELOADED_STATE__'";
        $pos = strpos($html, $needle);
    }
    if ($pos === false) {
        return null;
    }

    $scriptOpen = strrpos(substr($html, 0, $pos), '<script');
    if ($scriptOpen === false) {
        return null;
    }
    $start = strpos($html, '>', $scriptOpen);
    if ($start === false) {
        return null;
    }
    $start++;

    $end = strpos($html, '</script>', $start);
    if ($end === false) {
        return null;
    }

    $raw = substr($html, $start, $end - $start);
    $raw = trim($raw);
    if ($raw === '' || $raw[0] !== '{') {
        return null;
    }

    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return $json;
}

function ml_parse_products_from_preloaded_state($state) {
    $results = $state['pageState']['initialState']['results'] ?? null;
    if (!is_array($results) || count($results) === 0) {
        return [];
    }

    $items = [];
    foreach ($results as $result) {
        $poly = $result['polycard'] ?? null;
        if (!is_array($poly)) {
            continue;
        }
        $meta = $poly['metadata'] ?? [];
        $components = $poly['components'] ?? [];
        if (!is_array($components)) {
            $components = [];
        }

        $title = null;
        $priceValue = null;
        $previousPriceValue = null;
        $discountLabel = null;
        $soldQuantity = null;
        $soldText = null;
        $ratingValue = null;
        $shippingText = null;
        $shippingAdditional = null;

        foreach ($components as $component) {
            if (!is_array($component) || !isset($component['type'])) {
                continue;
            }
            if ($component['type'] === 'title' && isset($component['title']['text'])) {
                $title = trim((string)$component['title']['text']);
            }
            if ($component['type'] === 'price' && isset($component['price'])) {
                $p = $component['price'];
                if (isset($p['current_price']['value'])) {
                    $priceValue = (float)$p['current_price']['value'];
                }
                if (isset($p['previous_price']['value'])) {
                    $previousPriceValue = (float)$p['previous_price']['value'];
                }
                if (isset($p['discount_label']['text'])) {
                    $discountLabel = ml_normalize_discount_label($p['discount_label']['text']);
                }
            }
            if ($component['type'] === 'review_compacted' && isset($component['review_compacted'])) {
                $rc = $component['review_compacted'];
                if (isset($rc['values']) && is_array($rc['values'])) {
                    foreach ($rc['values'] as $v) {
                        if (!is_array($v)) {
                            continue;
                        }
                        if (($v['key'] ?? null) === 'label' && isset($v['label']['text'])) {
                            $ratingValue = (float)$v['label']['text'];
                        }
                        if (($v['key'] ?? null) === 'label2' && isset($v['label']['text'])) {
                            $soldText = trim((string)$v['label']['text']);
                            $soldQuantity = ml_parse_sold_quantity($soldText);
                        }
                    }
                }
                if ($soldQuantity === null && isset($rc['alt_text'])) {
                    $soldQuantity = ml_parse_sold_quantity($rc['alt_text']);
                }
            }
            if ($component['type'] === 'shipping' && isset($component['shipping'])) {
                $s = $component['shipping'];
                if (isset($s['text'])) {
                    $shippingText = trim((string)$s['text']);
                }
                $shippingAdditional = null;
            }
        }

        $pictureId = $poly['pictures']['pictures'][0]['id'] ?? null;
        $img = $pictureId ? ('https://http2.mlstatic.com/D_Q_NP_2X_' . $pictureId . '-V.webp') : null;

        if (!$title) {
            continue;
        }

        $productId = $meta['product_id'] ?? null;
        $productUrl = $productId ? ml_product_url_from_id($productId) : null;
        $clickUrl = ml_build_click_url($meta);
        $link = $productUrl ?: $clickUrl;

        $items[] = [
            'title' => $title,
            'link' => $link,
            'img' => $img,
            'price' => $priceValue !== null ? ml_format_brl($priceValue) : null,
            'price_value' => $priceValue,
            'previous_price_value' => $previousPriceValue,
            'discount_label' => $discountLabel,
            'sold_quantity' => $soldQuantity,
            'sold_text' => $soldText,
            'rating_count' => null,
            'rating_value' => $ratingValue,
            'shipping_text' => $shippingText,
            'shipping_additional_text' => $shippingAdditional,
            'recommended' => null,
            'ml_item_id' => $meta['id'] ?? null,
            'ml_product_id' => $productId,
            'ml_click_url' => $clickUrl,
        ];
    }

    return array_slice($items, 0, 50);
}

function ml_extract_jsonld_products($html) {
    $out = [];
    if (!preg_match_all('/<script[^>]+type="application\\/ld\\+json"[^>]*>(.*?)<\\/script>/s', $html, $matches)) {
        return $out;
    }
    foreach ($matches[1] as $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            continue;
        }
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }
        $products = [];
        ml_collect_products($data, $products);
        foreach ($products as $p) {
            $normalized = ml_normalize_product($p);
            if ($normalized) {
                $out[] = $normalized;
            }
        }
    }
    return $out;
}

function ml_parse_products_from_html($html) {
    $state = ml_extract_preloaded_state($html);
    $preloadedItems = $state ? ml_parse_products_from_preloaded_state($state) : [];
    $jsonldProducts = ml_extract_jsonld_products($html);

    $jsonldByTitle = [];
    $jsonldByProductId = [];
    foreach ($jsonldProducts as $p) {
        $k = ml_mb_strtolower(trim($p['title']));
        if ($k !== '') {
            $jsonldByTitle[$k] = $p;
        }
        if (!empty($p['product_id'])) {
            $jsonldByProductId[$p['product_id']] = $p;
        }
    }

    if ($preloadedItems) {
        foreach ($preloadedItems as &$item) {
            $j = null;
            if (!empty($item['ml_product_id']) && isset($jsonldByProductId[$item['ml_product_id']])) {
                $j = $jsonldByProductId[$item['ml_product_id']];
            } else {
                $k = ml_mb_strtolower(trim($item['title']));
                if ($k !== '' && isset($jsonldByTitle[$k])) {
                    $j = $jsonldByTitle[$k];
                }
            }

            if ($j) {
                // $item['link'] = $j['link']; // Keep preloaded link often more reliable for navigation
                if ($j['img']) {
                    $item['img'] = $j['img'];
                }
                $item['rating_count'] = $j['rating_count'];
                if ($item['rating_value'] === null) {
                    $item['rating_value'] = $j['rating_value'];
                }
            }

            if (empty($item['link'])) {
                if (!empty($item['ml_product_id'])) {
                    $item['link'] = ml_product_url_from_id($item['ml_product_id']);
                } elseif (!empty($item['ml_click_url'])) {
                    $item['link'] = $item['ml_click_url'];
                }
            }
        }
        unset($item);
        return array_slice($preloadedItems, 0, 50);
    }

    if ($jsonldProducts) {
        return array_slice($jsonldProducts, 0, 50);
    }

    $items = [];
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    $nodes = $xpath->query('//li[contains(@class,"ui-search-layout__item")]');
    if ($nodes->length === 0) {
        $nodes = $xpath->query('//div[contains(@class,"ui-search-result__wrapper")]');
    }

    foreach ($nodes as $node) {
        $titleNode = $xpath->query('.//h2[contains(@class,"ui-search-item__title")]', $node)->item(0);
        $linkNode = $xpath->query('.//a[contains(@class,"ui-search-link")]', $node)->item(0);
        $imgNode = $xpath->query('.//img', $node)->item(0);
        $priceNode = $xpath->query('.//*[contains(@class,"price-tag-fraction")]', $node)->item(0);

        $title = $titleNode ? trim($titleNode->textContent) : null;
        $link = $linkNode ? $linkNode->getAttribute('href') : null;
        $img = $imgNode ? $imgNode->getAttribute('data-src') : null;
        if (!$img && $imgNode) {
            $img = $imgNode->getAttribute('src');
        }
        $price = $priceNode ? trim($priceNode->textContent) : null;
        $priceValue = ml_parse_price_brl($price);

        if ($title && $link) {
            $items[] = [
                'title' => $title,
                'link' => $link,
                'img' => $img,
                'price' => $price,
                'price_value' => $priceValue,
                'previous_price_value' => null,
                'discount_label' => null,
                'sold_quantity' => null,
                'rating_count' => null,
                'rating_value' => null,
                'shipping_text' => null,
                'shipping_additional_text' => null,
                'recommended' => null,
            ];
        }
    }
    return array_slice($items, 0, 50);
}

function ml_search_items($query, $sort = '', $page = 1, $config = []) {
    $mlPageSize = 50;
    $items = [];
    $error = null;

    if (ml_is_url($query)) {
        [$html, $status, $curlErr] = ml_fetch_html($query, $config['user_agent'], $config['ml_cookie'] ?? '', 0, $config['ml_proxy'] ?? null);
        if ($html === false) {
            return ['items' => [], 'error' => 'Erro ao buscar a página: ' . $curlErr];
        } elseif ($status >= 400) {
            return ['items' => [], 'error' => 'Resposta HTTP inesperada: ' . $status];
        } else {
            $items = ml_parse_products_from_html($html);
        }
    } else {
        $sortSuffix = '';
        if ($sort === 'price_asc') {
            $sortSuffix = '_OrderId_PRICE';
        } elseif ($sort === 'best_sellers') {
            $sortSuffix = '_OrderId_BEST_SELLERS';
        }
        $offset = ($page - 1) * $mlPageSize + 1;
        $pageSuffix = $page > 1 ? '_Desde_' . $offset . '_NoIndex_True' : '';
        $url = $config['ml_base_url'] . rawurlencode($query) . $sortSuffix . $pageSuffix;

        [$html, $status, $curlErr] = ml_fetch_html($url, $config['user_agent'], $config['ml_cookie'] ?? '', 0, $config['ml_proxy'] ?? null);
        if ($html === false) {
            return ['items' => [], 'error' => 'Erro ao buscar a página: ' . $curlErr];
        } elseif ($status >= 400) {
            return ['items' => [], 'error' => 'Resposta HTTP inesperada: ' . $status];
        } else {
            $items = ml_parse_products_from_html($html);
        }
    }

    if (empty($items)) {
        $error = 'Nenhum resultado encontrado ou o layout mudou.';
    }

    return ['items' => $items, 'error' => $error];
}
