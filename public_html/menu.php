<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/public-menu.php';

header('Cache-Control: no-cache, must-revalidate, max-age=0');
$menu = public_menu_payload();
$restaurant = $menu['restaurant'];
$phoneDigits = preg_replace('/[^0-9+]/', '', (string)($restaurant['phone'] ?? ''));
$menuJson = json_encode(
    $menu,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#2b1b10">
  <meta name="description" content="<?= h($restaurant['name']) ?> — ციფრული მენიუ და აქტუალური ფასები">
  <meta name="robots" content="index,follow">
  <title><?= h($restaurant['name']) ?> — QR მენიუ</title>
  <link rel="icon" type="image/png" href="/Logo.png?v=12">
  <link rel="apple-touch-icon" href="/Logo.png?v=12">
  <link rel="stylesheet" href="/assets/menu.css?v=1">
</head>
<body class="qr-menu">
  <div class="menu-noise" aria-hidden="true"></div>

  <header class="menu-hero">
    <div class="menu-hero-inner">
      <div class="menu-brand">
        <div class="menu-logo-stage" aria-hidden="true">
          <img src="/Logo.png?v=12" alt="">
        </div>
        <div>
          <span class="menu-kicker">Premium QR Menu</span>
          <h1 class="menu-title"><?= h($restaurant['name']) ?></h1>
          <p class="menu-subtitle"><?= h($restaurant['name_en']) ?> · <?= (int)$menu['product_count'] ?> პროდუქტი</p>
        </div>
      </div>

      <div class="menu-hero-actions">
        <?php if ($phoneDigits !== ''): ?>
          <a class="menu-hero-button" href="tel:<?= h($phoneDigits) ?>" style="text-decoration:none">☎ დარეკვა</a>
        <?php endif; ?>
        <button class="menu-hero-button" id="menuShare" type="button">↗ გაზიარება</button>
      </div>
    </div>
  </header>

  <main class="menu-main">
    <section class="menu-toolbar" aria-label="მენიუს ძებნა და კატეგორიები">
      <div class="menu-search-row">
        <div class="menu-search">
          <input id="menuSearch" type="search" autocomplete="off" placeholder="მოძებნე კერძი ან სასმელი…" aria-label="მენიუში ძებნა">
          <button class="menu-search-clear" id="menuSearchClear" type="button" aria-label="ძებნის გასუფთავება" hidden>×</button>
        </div>
        <div class="menu-live-status"><span class="menu-live-dot" aria-hidden="true"></span><span id="menuStatusText">ფასები განახლებულია</span></div>
      </div>
      <nav class="menu-categories" id="menuCategories" aria-label="კატეგორიები"></nav>
    </section>

    <div id="menuSections"></div>

    <section class="menu-empty" id="menuEmpty" aria-live="polite">
      <strong>ასეთი პროდუქტი ვერ მოიძებნა</strong>
      <p>სცადე სხვა სიტყვა ან აირჩიე ყველა კატეგორია.</p>
    </section>
  </main>

  <footer class="menu-footer">
    <img class="menu-footer-logo" src="/Logo.png?v=12" alt="GARBALIA">
    <div><?= h($restaurant['name']) ?> · ფასები ავტომატურად ახლდება</div>
    <?php if (!empty($restaurant['address'])): ?><div><?= h($restaurant['address']) ?></div><?php endif; ?>
  </footer>

  <?php if (is_admin()): ?>
    <aside class="menu-admin-dock" aria-label="ადმინისტრატორის სწრაფი მართვა">
      <a href="/products">პროდუქტები</a>
      <a href="/menu-qr">QR-ის ბეჭდვა</a>
      <a href="/day">POS-ში დაბრუნება</a>
    </aside>
  <?php endif; ?>

  <button class="menu-top-button" id="menuTop" type="button" aria-label="გვერდის დასაწყისში დაბრუნება">↑</button>
  <div class="menu-toast" id="menuToast" role="status" aria-live="polite"></div>

  <script>window.__GARBALIA_MENU__ = <?= $menuJson ?: '{}' ?>;</script>
  <script src="/assets/menu.js?v=1" defer></script>
</body>
</html>
