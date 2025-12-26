<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ranking de Produtos</title>
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
        <a class="tab" href="featured.php">Produtos em Destaque</a>
        <a class="tab" href="ranking.php" aria-current="page">Ranking de Produtos</a>
      </nav>
      <div></div>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <div class="hero" style="margin-bottom:10px;">
        <svg class="hero__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M7 3h10v8a5 5 0 01-10 0V3zm2 2v6a3 3 0 006 0V5H9z" fill="currentColor"/>
          <path d="M5 5h2v2H6v1a3 3 0 01-1 2.2V5zm14 0v5.2A3 3 0 0118 8V7h-1V5h2z" fill="currentColor" opacity=".75"/>
          <path d="M9 19h6v2H9v-2z" fill="currentColor" opacity=".75"/>
        </svg>
        <div>
          <h1 style="margin-bottom:2px;">Ranking de Produtos</h1>
          <p style="margin-top:0;">Área pronta para exibir o ranking por demanda e margem.</p>
        </div>
      </div>
      <div class="placeholder">Em breve: tabela com filtros, ordenação e histórico.</div>
    </section>
  </main>
</body>
</html>
