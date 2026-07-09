<?php
session_start();
$configReady = file_exists(__DIR__ . '/config.php');
?><!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ცხრამეტი ნაოჭი POS</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="#"><span class="mark">19</span><span><strong>ცხრამეტი ნაოჭი POS</strong><small>ვებ-ჰოსტინგის ვერსია</small></span></a>
</header>
<main class="wrap">
  <section class="card narrow">
    <h1>POS პროექტი მზადდება</h1>
    <p class="muted">სტრუქტურა, ბაზა, კონფიგი და responsive დიზაინი უკვე ჩადებულია. შემდეგი კომიტით დაემატება სრული ფუნქციონალი: Login, დღის გახსნა/დახურვა, მაგიდები, შეკვეთები, ბეჭდვა და რეპორტები.</p>
    <?php if (!$configReady): ?>
      <div class="flash">ჰოსტინგზე ატვირთვისას config.example.php დააკოპირე როგორც config.php და ჩაწერე MySQL მონაცემები.</div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
