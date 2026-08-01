<?php
require __DIR__ . '/includes/bootstrap.php';

require_admin();

$host = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'pos.cours.ge'));
$menuUrl = 'https://' . $host . '/menu';
$qrSource = 'https://api.qrserver.com/v1/create-qr-code/?size=900x900&margin=28&format=png&data=' . rawurlencode($menuUrl);
$restaurantName = (string)cfg('restaurant_name', 'ცხრამეტი ნაოჭი');
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($restaurantName) ?> — QR კოდი</title>
  <link rel="icon" href="/Logo.png?v=12">
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 20% 0%,#f1d9b9,transparent 32%),linear-gradient(155deg,#f7efe4,#e9d7c0);color:#29180f;font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif}.qr-tools{position:fixed;left:18px;top:18px;display:flex;gap:8px;z-index:4}.qr-tools a,.qr-tools button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 13px;border:0;border-radius:12px;background:#2b1b10;color:#fff;text-decoration:none;font:inherit;font-weight:900;cursor:pointer}.qr-sheet{position:relative;width:min(520px,100%);overflow:hidden;padding:36px 32px 30px;border:1px solid rgba(80,47,25,.14);border-radius:34px;background:linear-gradient(160deg,#fffdf8,#f7e8d4);box-shadow:0 32px 90px rgba(43,27,16,.24);text-align:center}.qr-sheet:before{content:"";position:absolute;right:-55px;top:-58px;width:220px;height:180px;background:url('/Logo.png?v=12') center/contain no-repeat;opacity:.045;filter:brightness(0)}.qr-logo{position:relative;display:grid;place-items:center;width:90px;height:90px;margin:0 auto 16px;border-radius:28px;background:linear-gradient(145deg,#fff,#dfc29d);box-shadow:12px 15px 28px rgba(43,27,16,.18),inset 0 1px 0 #fff}.qr-logo img{width:66px;height:56px;object-fit:contain}.qr-kicker{display:block;color:#986b37;font-size:.72rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase}.qr-sheet h1{margin:8px 0 6px;font-size:2rem;line-height:1;font-weight:950;letter-spacing:-.045em}.qr-sheet p{margin:0 auto 20px;max-width:390px;color:#705746;font-weight:800;line-height:1.45}.qr-frame{position:relative;width:min(330px,100%);margin:0 auto;padding:16px;border:1px solid rgba(43,27,16,.12);border-radius:28px;background:#fff;box-shadow:0 18px 40px rgba(43,27,16,.14),inset 0 1px 0 #fff}.qr-frame img{display:block;width:100%;height:auto;border-radius:14px}.qr-url{margin:17px auto 0;padding:10px 12px;border-radius:13px;background:rgba(255,255,255,.68);color:#4e3729;font-size:.78rem;font-weight:900;overflow-wrap:anywhere}.qr-note{margin-top:14px;color:#8a725f;font-size:.73rem;font-weight:750}@media(max-width:600px){body{padding:12px}.qr-tools{position:static;width:100%;margin-bottom:10px;justify-content:center;flex-wrap:wrap}.qr-sheet{padding:27px 16px 22px;border-radius:26px}.qr-sheet h1{font-size:1.65rem}.qr-logo{width:76px;height:76px;border-radius:23px}.qr-logo img{width:56px;height:48px}}@media print{body{padding:0;background:#fff}.qr-tools{display:none}.qr-sheet{width:100%;max-width:520px;border:0;box-shadow:none;border-radius:0;padding:24px}.qr-note{display:none}@page{size:A5 portrait;margin:8mm}}
  </style>
</head>
<body>
  <div class="qr-tools">
    <a href="/menu" target="_blank" rel="noopener">მენიუს გახსნა</a>
    <button type="button" onclick="window.print()">QR-ის ბეჭდვა</button>
    <a href="/day">POS-ში დაბრუნება</a>
  </div>

  <main class="qr-sheet">
    <div class="qr-logo"><img src="/Logo.png?v=12" alt="GARBALIA"></div>
    <span class="qr-kicker">Scan & Explore</span>
    <h1><?= h($restaurantName) ?></h1>
    <p>დაასკანერე QR კოდი და იხილე ჩვენი ციფრული მენიუ აქტუალური ფასებით.</p>
    <div class="qr-frame"><img src="<?= h($qrSource) ?>" alt="მენიუს QR კოდი"></div>
    <div class="qr-url"><?= h($menuUrl) ?></div>
    <div class="qr-note">ეს გვერდი ადმინისტრატორისთვისაა — დაბეჭდე და განათავსე მაგიდებზე.</div>
  </main>
</body>
</html>
