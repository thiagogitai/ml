<?php
$config = require __DIR__ . '/config.php';
$hasCookie = trim($config['ml_cookie']) !== '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pesquisa e Insights</title>
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
      <div class="hero">
        <svg class="hero__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 2l.6 4.1a5.9 5.9 0 003.3 4.7L20 12l-4.1 1.2a5.9 5.9 0 00-3.3 4.7L12 22l-.6-4.1a5.9 5.9 0 00-3.3-4.7L4 12l4.1-1.2a5.9 5.9 0 003.3-4.7L12 2z" fill="currentColor"/>
          <path d="M20.5 3.5l.3 2a2.9 2.9 0 001.6 2.3l2 1-2 .6a2.9 2.9 0 00-1.6 2.3l-.3 2-.3-2a2.9 2.9 0 00-1.6-2.3l-2-.6 2-1a2.9 2.9 0 001.6-2.3l.3-2z" fill="currentColor" opacity=".7"/>
        </svg>
        <div>
          <h1>Pesquisa Inteligente</h1>
          <p>Busque produtos no Mercado Livre e use o Alibaba Lens externamente para coletar preços; insira os dados aqui para calcular a viabilidade.</p>
        </div>
      </div>

      <div class="segmented" role="group" aria-label="Modo de pesquisa">
        <button class="segmented__btn" type="button" id="seg-search" aria-pressed="true">Pesquisar</button>
        <button class="segmented__btn" type="button" id="seg-link" aria-pressed="false">Link do Mercado Livre</button>
      </div>

      <form class="form" action="search.php" method="get" id="form-search">
        <div class="row">
          <input class="field row__grow" id="q" name="q" type="text" placeholder="Pesquise um produto" required />
          <div class="btns">
            <button class="btn btn--primary" type="submit">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M10.5 3a7.5 7.5 0 105.1 13l4.2 4.2a1 1 0 001.4-1.4l-4.2-4.2A7.5 7.5 0 0010.5 3zm0 2a5.5 5.5 0 110 11 5.5 5.5 0 010-11z" fill="currentColor"/>
              </svg>
              Buscar produtos
            </button>
          </div>
        </div>

        <p class="hint">Digite o nome de um produto e clique em "Buscar produtos" para buscar e analisar.</p>
        <input type="hidden" name="sort" value="" />
      </form>

      <form class="form" action="search.php" method="get" id="form-link" style="display:none;">
        <div class="row">
          <input class="field row__grow" id="ml_link" name="q" type="text" placeholder="Cole um link do Mercado Livre (ex: https://lista.mercadolivre.com.br/fone)" required />
          <div class="btns">
            <button class="btn btn--primary" type="submit">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M8.6 15.4a4 4 0 010-5.7l1.4-1.4a4 4 0 015.7 0l.6.6-1.4 1.4-.6-.6a2 2 0 00-2.9 0L10 11a2 2 0 000 2.9l.6.6-1.4 1.4-.6-.6zm6.8-6.8l.6.6a4 4 0 010 5.7l-1.4 1.4a4 4 0 01-5.7 0l-.6-.6 1.4-1.4.6.6a2 2 0 002.9 0L14 13a2 2 0 000-2.9l-.6-.6 1.4-1.4z" fill="currentColor"/>
              </svg>
              Buscar produtos
            </button>
          </div>
        </div>
        <p class="hint">Cole o link e use "Buscar produtos" para abrir a listagem e extrair os dados.</p>
        <input type="hidden" name="sort" value="" />
      </form>

      <?php if (!$hasCookie): ?>
        <div class="alert">Configure o cookie de sessão em <code>config.php</code> para evitar bloqueios.</div>
      <?php endif; ?>
    </section>
  </main>

  <script>
    (function () {
      var segSearch = document.getElementById('seg-search');
      var segLink = document.getElementById('seg-link');
      var formSearch = document.getElementById('form-search');
      var formLink = document.getElementById('form-link');

      function setMode(mode) {
        var isSearch = mode === 'search';
        segSearch.setAttribute('aria-pressed', isSearch ? 'true' : 'false');
        segLink.setAttribute('aria-pressed', isSearch ? 'false' : 'true');
        formSearch.style.display = isSearch ? '' : 'none';
        formLink.style.display = isSearch ? 'none' : '';
        try { localStorage.setItem('ml_mode', mode); } catch (e) {}
      }

      segSearch.addEventListener('click', function(){ setMode('search'); });
      segLink.addEventListener('click', function(){ setMode('link'); });

      try {
        var saved = localStorage.getItem('ml_mode');
        if (saved === 'link') setMode('link');
      } catch (e) {}
    }());
  </script>
</body>
</html>

