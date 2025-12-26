<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Produtos em Destaque</title>
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
        <a class="tab" href="index.php">Pesquisa e Insights</a>
        <a class="tab" href="featured.php" aria-current="page">Produtos em Destaque</a>
        <a class="tab" href="ranking.php">Ranking de Produtos</a>
      </nav>
      <div></div>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <div class="hero" style="margin-bottom:10px;">
        <svg class="hero__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 2l.6 4.1a5.9 5.9 0 003.3 4.7L20 12l-4.1 1.2a5.9 5.9 0 00-3.3 4.7L12 22l-.6-4.1a5.9 5.9 0 00-3.3-4.7L4 12l4.1-1.2a5.9 5.9 0 003.3-4.7L12 2z" fill="currentColor"/>
        </svg>
        <div>
          <h1 style="margin-bottom:2px;">Produtos em Destaque</h1>
          <p style="margin-top:0;">Área pronta para listar produtos monitorados.</p>
        </div>
      </div>
      <div class="placeholder">Em breve: cards de produtos com margens, variação de preço e alertas.</div>
    </section>
  </main>
</body>
</html>
