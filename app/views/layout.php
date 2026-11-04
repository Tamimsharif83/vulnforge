<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'VulnForge') ?></title>
  <link rel="icon" href="data:,">
  <link rel="stylesheet" href="/assets/app.css">
  <script>
    const savedTheme = localStorage.getItem('vf-theme');
    if (savedTheme) document.documentElement.dataset.theme = savedTheme;
  </script>
</head>
<body>
  <div class="banner">Intentionally vulnerable. Run only on your own machine, on localhost.</div>
  <header class="topbar">
    <a class="brand" href="/">VulnForge</a>
    <nav>
      <a href="/">Labs</a>
      <button type="button" id="theme-toggle" class="ghost">Theme</button>
    </nav>
  </header>
  <main><?= $content ?></main>
  <script src="/assets/app.js"></script>
</body>
</html>
