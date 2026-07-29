<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$orderId = (int)($_GET['order_id'] ?? 0);
$isReprint = (int)($_GET['reprint'] ?? 0) === 1;
$order = fetch_order($orderId);

if (!$order || ($order['status'] ?? '') !== 'closed') {
    flash('დახურული ანგარიში ვერ მოიძებნა.', 'warn');
    redirect_to($isReprint ? 'history' : 'tables');
}

if (!is_admin()) {
    $orderDate = date('Y-m-d', strtotime($order['closed_at'] ?: $order['created_at']));
    $limitFrom = date('Y-m-d', strtotime('-6 days'));
    if ($orderDate < $limitFrom || $orderDate > date('Y-m-d')) {
        flash('მოლარეს ქვითრის ხელახლა ბეჭდვა მხოლოდ ბოლო 7 დღის ანგარიშებზე შეუძლია.', 'warn');
        redirect_to('history');
    }
}

$table = fetch_table((int)$order['table_id']);
if (!$table) {
    flash('მაგიდა ვერ მოიძებნა.', 'warn');
    redirect_to($isReprint ? 'history' : 'tables');
}

$items = order_items($orderId);
render_header($isReprint ? 'ქვითრის ხელახლა ბეჭდვა' : 'საბოლოო ანგარიში');
?>
<style>
.reprint-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.reprint-head h1{margin:0}.reprint-note{margin:7px 0 0;color:var(--muted);font-size:.88rem;font-weight:800}.reprint-badge{display:inline-flex;align-items:center;padding:7px 11px;border-radius:999px;background:#fff3cd;color:#795000;font-size:.78rem;font-weight:950}.reprint-page .receipt-card{max-width:560px;margin:0 auto}.reprint-actions{display:flex;gap:8px;flex-wrap:wrap}.reprint-actions .btn{white-space:nowrap}@media(max-width:560px){.reprint-actions{width:100%}.reprint-actions .btn{width:100%}}
</style>
<section class="reprint-page">
  <div class="page-head reprint-head">
    <div>
      <h1><?= $isReprint ? 'ქვითრის ხელახლა ბეჭდვა' : 'საბოლოო ანგარიში' ?></h1>
      <p class="reprint-note">ანგარიში #<?= (int)$order['id'] ?> · <?= h($table['name']) ?> · <?= h($order['closed_at'] ?: $order['created_at']) ?></p>
    </div>
    <div class="reprint-actions">
      <?php if ($isReprint): ?><span class="reprint-badge">ისტორიის ასლი</span><a class="btn" href="<?= h(url_for('history', ['order_id'=>(int)$order['id']])) ?>">ისტორიაში დაბრუნება</a><?php else: ?><a class="btn" href="<?= h(url_for('tables')) ?>">მაგიდებზე დაბრუნება</a><?php endif; ?>
    </div>
  </div>
  <section class="print-grid single">
    <?= receipt_card('final_receipt', 'სალაროს საბოლოო ანგარიში', build_final_receipt($table, $order, $items)) ?>
  </section>
</section>
<?php render_footer();
