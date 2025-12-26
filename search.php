<?php
require_once __DIR__ . '/ml_scraper.php';
$config = require __DIR__ . '/config.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$debug = isset($_GET['debug']) ? (int)$_GET['debug'] : 0;

if ($query === '') {
    header('Location: index.php');
    exit;
}

$mlPageSize = 50;
$result = ml_search_items($query, $sort, $page, $config);
$items = $result['items'];
$error = $result['error'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resultados - Pesquisa e Insights</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/app.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar__inner">
      <a class="brand" href="index.php" aria-label="Avia">
        <img src="avia-logo.svg" alt="Avia" />
      </a>
      <nav class="topbar__tabs" aria-label="Seções">
        <a class="tab" href="index.php" aria-current="page">Pesquisa e Insights</a>
        <a class="tab" href="featured.php">Produtos em Destaque</a>
        <a class="tab" href="ranking.php">Ranking de Produtos</a>
      </nav>
      <div></div>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <div class="hero" style="margin-bottom:10px;">
        <svg class="hero__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M10.5 3a7.5 7.5 0 105.1 13l4.2 4.2a1 1 0 001.4-1.4l-4.2-4.2A7.5 7.5 0 0010.5 3zm0 2a5.5 5.5 0 110 11 5.5 5.5 0 010-11z" fill="currentColor"/>
        </svg>
        <div>
          <h1 style="margin-bottom:2px;">Resultados</h1>
          <p style="margin-top:0;">Termo: <strong><?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?></strong> | <a href="index.php">Nova busca</a></p>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php else: ?>
        <div class="grid">
          <?php foreach ($items as $item): ?>
            <?php $isViable = !empty($item['sold_quantity']) && (int)$item['sold_quantity'] >= 500; ?>
            <div class="pitem<?php echo $isViable ? ' pitem--viable' : ''; ?>">
              <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                <div class="pitem__brand">Mercado Livre</div>
                <?php if (!empty($item['discount_label'])): ?>
                  <div class="badge badge--ok" style="border-color:rgba(0,166,80,.22);background:rgba(0,166,80,.10);color:#05603a;"><?php echo htmlspecialchars($item['discount_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php else: ?>
                  <div></div>
                <?php endif; ?>
              </div>
              <?php if ($item['img']): ?>
                <img src="<?php echo htmlspecialchars($item['img'], ENT_QUOTES, 'UTF-8'); ?>" alt="" />
              <?php endif; ?>
              <div class="pitem__title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></div>

              <div class="pitem__summary">
                <div>
                  <?php if ($item['price'] !== null): ?>
                    <div class="pitem__price"><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php else: ?>
                    <div class="pitem__price">Preço n/d</div>
                  <?php endif; ?>
                  <?php if (!empty($item['previous_price_value'])): ?>
                    <div class="pitem__sub">
                      <span class="pitem__strike"><?php echo htmlspecialchars(ml_format_brl($item['previous_price_value']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($item['shipping_text'])): ?>
                    <div class="pitem__sub">
                      <span class="badge badge--na"><?php echo htmlspecialchars($item['shipping_text'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php if (!empty($item['shipping_additional_text'])): ?>
                        <span class="pitem__muted"><?php echo htmlspecialchars($item['shipping_additional_text'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="pitem__facts">
                  <div class="pitem__facts-row">
                    <?php if ($isViable): ?>
                      <span class="badge badge--viable" title="Mais de 500 vendas">
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
                          <path fill="currentColor" d="M12 2l3.2 5.3 6 1.3-4.1 4.6.6 6.2L12 17.6 6.3 19.4l.6-6.2L2.8 8.6l6-1.3L12 2z"/>
                        </svg>
                        Viável
                      </span>
                    <?php endif; ?>
                    <span class="badge badge--na">Vendas: <?php echo !empty($item['sold_text']) ? htmlspecialchars($item['sold_text'], ENT_QUOTES, 'UTF-8') : 'n/d'; ?></span>
                    <?php if ($item['rating_count'] !== null): ?>
                      <span class="badge badge--na">Avaliações: <?php echo (int)$item['rating_count']; ?><?php if ($item['rating_value'] !== null): ?> (<?php echo number_format((float)$item['rating_value'], 1, ',', '.'); ?>)<?php endif; ?></span>
                    <?php else: ?>
                      <span class="badge badge--na">Avaliações: n/d<?php if ($item['rating_value'] !== null): ?> (<?php echo number_format((float)$item['rating_value'], 1, ',', '.'); ?>)<?php endif; ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <a class="pitem__link" href="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noreferrer">Ver no Mercado Livre</a>
              <a class="pitem__alibaba" href="#"
                data-alibaba-title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>"
                data-alibaba-image="<?php echo htmlspecialchars((string)($item['img'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                Pesquisar no Alibaba
              </a>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="pager">
          <?php if ($page > 1): ?>
            <a href="search.php?q=<?php echo urlencode($query); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>">Anterior</a>
          <?php endif; ?>
          <span>Página <?php echo (int)$page; ?></span>
          <?php
            $hasNext = false;
            $hasNext = count($items) === $mlPageSize;
          ?>
          <?php if ($hasNext): ?>
            <a href="search.php?q=<?php echo urlencode($query); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>">Próxima</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <aside class="drawer" id="alibabaDrawer" aria-hidden="true">
    <div class="drawer__backdrop" data-drawer-close="1"></div>
    <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="Alibaba">
      <div class="drawer__header">
        <div>
          <div class="drawer__title">Alibaba (busca por imagem)</div>
          
        </div>
        <button class="drawer__close" type="button" data-drawer-close="1">Fechar</button>
      </div>
      <div class="drawer__body">
        <div class="drawer__preview">
          <img id="alibabaPreviewImg" alt="" />
          <div class="drawer__text">
            <div id="alibabaPreviewText"></div>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <a class="pitem__link" id="alibabaOpenTab" target="_blank" rel="noreferrer">Abrir no Alibaba</a>
          <button class="drawer__close" style="height:40px;" type="button" id="alibabaFetch">Carregar resultados aqui</button>
        </div>
        <div id="alibabaStatus" style="font-size:12px;color:#6b7280;font-weight:650;"></div>
        <div id="alibabaResults" style="display:grid;grid-template-columns:1fr;gap:10px;"></div>
      </div>
    </div>
  </aside>

  <script>
    (function () {
      var drawer = document.getElementById('alibabaDrawer');
      var openTab = document.getElementById('alibabaOpenTab');
      var previewImg = document.getElementById('alibabaPreviewImg');
      var previewText = document.getElementById('alibabaPreviewText');
      var fetchBtn = document.getElementById('alibabaFetch');
      var statusEl = document.getElementById('alibabaStatus');
      var resultsEl = document.getElementById('alibabaResults');

      function buildAlibabaImageSearchUrl(imagePath, regions) {
        var base = 'https://www.alibaba.com/search/page';
        var params = [
          'tab=all',
          'SearchScene=imageTextSearch',
          'imagePath=' + encodeURIComponent(imagePath || ''),
          'from=pcDetailHeader'
        ];
        if (regions) {
          params.push('regions=' + encodeURIComponent(regions));
        }
        return base + '?' + params.join('&');
      }

      function setStatus(text) {
        if (!statusEl) return;
        statusEl.textContent = text || '';
      }

      function renderResults(json) {
        if (!resultsEl) return;
        resultsEl.innerHTML = '';

        function normalizeUrl(s) {
          if (!s) return null;
          if (typeof s !== 'string') return null;
          if (s.startsWith('//')) return 'https:' + s;
          if (/^https?:\/\//i.test(s)) return s;
          return null;
        }

        // Preferred: Alibaba response format `data.offers` (open-s `imageSearchViewService`).
        var offers = null;
        try {
          offers = json && json.data && Array.isArray(json.data.offers) ? json.data.offers : null;
          if (!offers && json && json.data && json.data.model && Array.isArray(json.data.model.offers)) offers = json.data.model.offers;
        } catch (e) {}

        var candidates = [];
        if (offers && offers.length) {
          offers.slice(0, 20).forEach(function (o) {
            var title = o.title || o.subject || o.name;
            var url = normalizeUrl(o.productUrl || o.product_url || o.detailUrl || o.url);
            if (typeof title === 'string' && title && url) {
              var imgs = [];
              try {
                if (o.multiImage && Array.isArray(o.multiImage)) {
                  o.multiImage.forEach(function (u) {
                    var nu = normalizeUrl(u);
                    if (nu) imgs.push(nu);
                  });
                }
                var mainImg = normalizeUrl(o.mainImage || null);
                if (mainImg && imgs.indexOf(mainImg) === -1) imgs.unshift(mainImg);
              } catch (e) {}
              imgs = imgs.filter(Boolean).slice(0, 6);
              candidates.push({
                title: title,
                url: url,
                price: o.priceV2 || o.price || o.promotionPriceV2 || o.promotionPrice || null,
                moq: o.moqV2 || null,
                sold: (o.marketingPowerCommon && o.marketingPowerCommon.text) ? o.marketingPowerCommon.text : (o.soldOrder || null),
                company: o.companyName || null,
                country: o.countryCode || null,
                img: imgs.length ? imgs[0] : null,
                imgs: imgs
              });
            }
          });
        }

        // Fallback heuristic: collect result-like objects that contain a title and a URL.
        if (!candidates.length) {
          var seen = new Set();
          function walk(obj) {
            if (!obj || candidates.length >= 20) return;
            if (Array.isArray(obj)) { obj.forEach(walk); return; }
            if (typeof obj !== 'object') return;
            var title = obj.title || obj.subject || obj.name;
            var url = normalizeUrl(obj.detailUrl || obj.detail_url || obj.productUrl || obj.product_url || obj.url);
            if (typeof title === 'string' && title.length && url) {
              var key = title + '|' + url;
              if (!seen.has(key)) {
                seen.add(key);
                candidates.push({ title: title, url: url });
              }
            }
            Object.keys(obj).forEach(function (k) { walk(obj[k]); });
          }
          walk(json);
        }

        if (!candidates.length) {
          var pre = document.createElement('pre');
          pre.style.whiteSpace = 'pre-wrap';
          pre.style.wordBreak = 'break-word';
          pre.style.margin = '0';
          pre.textContent = JSON.stringify(json, null, 2);
          resultsEl.appendChild(pre);
          return;
        }

        candidates.forEach(function (c) {
          var wrap = document.createElement('div');
          wrap.style.border = '1px solid rgba(17,24,39,.10)';
          wrap.style.borderRadius = '12px';
          wrap.style.padding = '10px';
          wrap.style.background = '#fff';

          if (c.img) {
            var media = document.createElement('div');
            media.style.float = 'right';
            media.style.marginLeft = '10px';
            media.style.display = 'flex';
            media.style.flexDirection = 'column';
            media.style.alignItems = 'flex-end';
            media.style.gap = '6px';

            var img = document.createElement('img');
            img.src = c.img;
            img.alt = '';
            img.style.width = '72px';
            img.style.height = '72px';
            img.style.objectFit = 'contain';
            img.style.borderRadius = '12px';
            img.style.background = '#fafafa';
            img.style.border = '1px solid rgba(17,24,39,.10)';
            media.appendChild(img);

            if (c.imgs && c.imgs.length > 1) {
              var thumbs = document.createElement('div');
              thumbs.style.display = 'flex';
              thumbs.style.gap = '6px';
              thumbs.style.justifyContent = 'flex-end';
              c.imgs.slice(1, 5).forEach(function (u) {
                var th = document.createElement('img');
                th.src = u;
                th.alt = '';
                th.style.width = '28px';
                th.style.height = '28px';
                th.style.objectFit = 'cover';
                th.style.borderRadius = '8px';
                th.style.background = '#fafafa';
                th.style.border = '1px solid rgba(17,24,39,.10)';
                thumbs.appendChild(th);
              });
              media.appendChild(thumbs);
            }

            wrap.appendChild(media);
          }

          var t = document.createElement('div');
          t.style.fontWeight = '800';
          t.style.fontSize = '13px';
          t.style.lineHeight = '1.35';
          t.textContent = c.title;

          var meta = document.createElement('div');
          meta.style.marginTop = '6px';
          meta.style.display = 'flex';
          meta.style.flexWrap = 'wrap';
          meta.style.gap = '8px';
          meta.style.alignItems = 'center';
          meta.style.color = '#6b7280';
          meta.style.fontSize = '12px';
          meta.style.fontWeight = '650';
          var parts = [];
          if (c.price) parts.push('Preço: ' + c.price);
          if (c.moq) parts.push(c.moq);
          if (c.sold) parts.push(c.sold);
          if (c.company) parts.push(c.company);
          if (c.country) parts.push(c.country);
          meta.textContent = parts.join(' | ');

          var a = document.createElement('a');
          a.href = c.url;
          a.target = '_blank';
          a.rel = 'noreferrer';
          a.textContent = c.price ? ('Abrir no Alibaba — ' + c.price) : 'Abrir no Alibaba';
          a.style.display = 'inline-flex';
          a.style.marginTop = '8px';
          a.style.fontWeight = '800';
          a.style.color = '#111';
          a.style.textDecoration = 'none';
          a.style.border = '1px solid rgba(17,24,39,.14)';
          a.style.borderRadius = '10px';
          a.style.height = '34px';
          a.style.alignItems = 'center';
          a.style.padding = '0 10px';
          a.onmouseenter = function(){ a.style.background = '#f3f4f6'; };
          a.onmouseleave = function(){ a.style.background = '#fff'; };

          wrap.appendChild(t);
          if (meta.textContent) wrap.appendChild(meta);
          wrap.appendChild(a);
          resultsEl.appendChild(wrap);
        });
      }

      async function fetchAlibaba(imagePath, region) {
        if (!imagePath) {
          setStatus('Sem URL de imagem.');
          return;
        }
        setStatus('Carregando resultados do Alibaba…');
        if (resultsEl) resultsEl.innerHTML = '';

        try {
          var url;
          if (String(imagePath).indexOf('/icbuimgsearch/') === 0) {
            url = 'alibaba_image_search.php?imageAddress=' + encodeURIComponent(imagePath) + '&pageSize=20&beginPage=1&language=pt&token=<?php echo htmlspecialchars($config["api_token"], ENT_QUOTES, "UTF-8"); ?>';
          } else {
            // 1) Tenta upload (picUrl/imageUrl) para obter /icbuimgsearch/... e melhorar a busca por imagem.
            // 2) Se falhar, cai no modo direto por URL.
            try {
              var upRes = await fetch('alibaba_image_upload.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'imageUrl=' + encodeURIComponent(imagePath) + '&language=pt&token=<?php echo htmlspecialchars($config["api_token"], ENT_QUOTES, "UTF-8"); ?>'
              });
              var up = await upRes.json();
              if (up && up.ok && up.imageAddress && String(up.imageAddress).indexOf('/icbuimgsearch/') === 0) {
                imagePath = up.imageAddress;
                if (openTab && up.ossUrl) {
                  openTab.href = buildAlibabaImageSearchUrl(up.ossUrl, '');
                }
                url = 'alibaba_image_search.php?imageAddress=' + encodeURIComponent(imagePath) + '&pageSize=20&beginPage=1&language=pt&token=<?php echo htmlspecialchars($config["api_token"], ENT_QUOTES, "UTF-8"); ?>';
              } else {
                url = 'alibaba_image_search.php?imagePath=' + encodeURIComponent(imagePath) + '&pageSize=20&beginPage=1&language=pt&token=<?php echo htmlspecialchars($config["api_token"], ENT_QUOTES, "UTF-8"); ?>';
              }
            } catch (e) {
              url = 'alibaba_image_search.php?imagePath=' + encodeURIComponent(imagePath) + '&pageSize=20&beginPage=1&language=pt&token=<?php echo htmlspecialchars($config["api_token"], ENT_QUOTES, "UTF-8"); ?>';
            }
          }
          if (region) url += '&region=' + encodeURIComponent(region);
          var res = await fetch(url, { credentials: 'same-origin' });
          var data = await res.json();
          if (!data.ok) {
            setStatus(data.error || 'Falha ao consultar Alibaba.');
            return;
          }
          setStatus('Resultados carregados.');
          renderResults(data.data);
        } catch (e) {
          setStatus('Erro ao carregar resultados.');
        }
      }

      function openDrawer(opts) {
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        var title = (opts && opts.title) ? opts.title : '';
        var img = (opts && opts.img) ? opts.img : '';
        if (previewImg) previewImg.src = img || '';
        if (previewText) previewText.textContent = title ? ('Produto: ' + title) : '';
        var url = img ? buildAlibabaImageSearchUrl(img, '') : ('https://www.alibaba.com/trade/search?SearchText=' + encodeURIComponent(title || ''));
        if (openTab) openTab.href = url;
        if (fetchBtn) fetchBtn.onclick = function(){
          if (openTab) openTab.href = buildAlibabaImageSearchUrl(img, '');
          fetchAlibaba(img, '');
        };
        setStatus('');
        if (resultsEl) resultsEl.innerHTML = '';

        // Auto-carregar ao abrir (sem inventar nada: apenas consulta o endpoint e renderiza o retorno).
        if (img) {
          fetchAlibaba(img, '');
        }
      }

      function closeDrawer() {
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }

      document.querySelectorAll('[data-drawer-close=\"1\"]').forEach(function (el) {
        el.addEventListener('click', closeDrawer);
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') closeDrawer();
      });

      document.querySelectorAll('.pitem__alibaba').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          try { e.preventDefault(); } catch (e2) {}
          openDrawer({
            title: btn.getAttribute('data-alibaba-title') || '',
            img: btn.getAttribute('data-alibaba-image') || ''
          });
        });
      });

    }());
  </script>

</body>
</html>
