<?php
require __DIR__ . '/includes/bootstrap.php';

require_admin();
ensure_order_discount_columns();

function stat_date_range(string $range): array {
    $today = date('Y-m-d');
    return match ($range) {
        'today' => [$today, $today, 'დღეს'],
        'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day')), 'გუშინ'],
        'last7' => [date('Y-m-d', strtotime('-6 day')), $today, 'ბოლო 7 დღე'],
        'prev_month' => [date('Y-m-01', strtotime('first day of previous month')), date('Y-m-t', strtotime('last day of previous month')), 'წინა თვე'],
        'year' => [date('Y-01-01'), $today, 'ამ წელს'],
        default => [date('Y-m-01'), $today, 'ამ თვეში'],
    };
}

function sales_between(string $from, string $to): array {
    $stmt = db()->prepare('SELECT COUNT(*) orders_count, COALESCE(SUM(total),0) total FROM orders WHERE status="closed" AND COALESCE(closed_at, created_at) BETWEEN ? AND ?');
    $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
    $row = $stmt->fetch() ?: [];
    return ['orders_count' => (int)($row['orders_count'] ?? 0), 'total' => (float)($row['total'] ?? 0)];
}

$range = $_GET['range'] ?? 'month';
[$from, $to, $rangeLabel] = stat_date_range($range);
$todaySales = sales_between(date('Y-m-d'), date('Y-m-d'));
$yesterdaySales = sales_between(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day')));
$monthSales = sales_between(date('Y-m-01'), date('Y-m-d'));
$prevMonthSales = sales_between(date('Y-m-01', strtotime('first day of previous month')), date('Y-m-t', strtotime('last day of previous month')));

$stmt = db()->prepare('SELECT oi.product_name, SUM(oi.quantity) qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status="closed" AND oi.is_cancelled=0 AND COALESCE(o.closed_at,o.created_at) BETWEEN ? AND ? GROUP BY oi.product_name ORDER BY qty DESC, oi.product_name ASC LIMIT 12');
$stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
$topProducts = $stmt->fetchAll();

$stmt = db()->prepare('SELECT t.name table_name, COUNT(o.id) orders_count, COALESCE(SUM(o.total),0) total FROM restaurant_tables t LEFT JOIN orders o ON o.table_id=t.id AND o.status="closed" AND COALESCE(o.closed_at,o.created_at) BETWEEN ? AND ? WHERE t.is_active=1 GROUP BY t.id, t.name, t.sort_order ORDER BY orders_count DESC, total DESC, t.sort_order ASC, t.id ASC');
$stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
$tableStats = $stmt->fetchAll();

$rangeUrl = fn(string $r) => h(url_for('statistics', ['range' => $r]));

render_header('სტატისტიკა');
?>
<style>
.statistics-page{display:grid;gap:16px}.statistics-page *{min-width:0}.statistics-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap}.statistics-head h1{margin:0}.statistics-sub{margin:6px 0 0;color:var(--muted);font-size:.88rem;font-weight:750}.stats-range{display:flex;gap:7px;flex-wrap:wrap;align-items:center}.stats-range .btn{min-height:36px;padding:7px 10px;border-radius:11px;font-size:.82rem;line-height:1;white-space:nowrap}.stats-range .active{background:var(--green)!important}.stat-mini-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.stat-mini{background:var(--brown);color:#fff;border-radius:17px;padding:13px 14px;box-shadow:var(--shadow);overflow:hidden}.stat-mini span{display:block;opacity:.76;font-size:.76rem;font-weight:850;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.stat-mini strong{display:block;margin-top:5px;font-size:clamp(1.05rem,2vw,1.36rem);line-height:1.1;font-weight:950;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.statistics-two{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start}.stat-card{padding:16px!important}.stat-card h2{font-size:1.02rem;margin:0 0 10px}.stat-table{width:100%;border:0!important;overflow:hidden!important}.stat-table table{width:100%;min-width:0!important;table-layout:fixed;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid var(--line);border-radius:15px;overflow:hidden}.stat-table th,.stat-table td{padding:9px 10px;font-size:.84rem;line-height:1.2;vertical-align:middle;border-bottom:1px solid var(--line);white-space:normal;overflow-wrap:anywhere}.stat-table th{background:#ead9c4;font-size:.78rem}.stat-table tr:last-child td{border-bottom:0}.stat-rank{width:46px;color:var(--muted);font-weight:950}.stat-num{text-align:right;font-weight:950;white-space:nowrap!important}.empty-stat{border:1px dashed var(--line);border-radius:15px;padding:14px;color:var(--muted);font-size:.88rem;font-weight:800;background:#fffaf2}@media(max-width:900px){.stat-mini-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.statistics-two{grid-template-columns:1fr}.statistics-head{align-items:flex-start}.stats-range{width:100%}.stats-range .btn{flex:1 1 auto}}@media(max-width:520px){.stat-mini-grid{grid-template-columns:1fr}.stat-mini{padding:12px}.stats-range .btn{width:100%;font-size:.82rem}.stat-table th,.stat-table td{font-size:.80rem;padding:8px}.stat-rank{width:34px}.statistics-sub{font-size:.82rem}}
</style>
<section class="statistics-page">
  <div class="statistics-head">
    <div>
      <h1>სტატისტიკა</h1>
      <p class="statistics-sub">მხოლოდ მთავარი მაჩვენებლები — გაყიდვები, ტოპ პროდუქტები და მაგიდების დატვირთვა.</p>
    </div>
    <div class="stats-range">
      <a class="btn <?= $range==='today'?'active':'' ?>" href="<?= $rangeUrl('today') ?>">დღეს</a>
      <a class="btn <?= $range==='yesterday'?'active':'' ?>" href="<?= $rangeUrl('yesterday') ?>">გუშინ</a>
      <a class="btn <?= $range==='last7'?'active':'' ?>" href="<?= $rangeUrl('last7') ?>">ბოლო 7 დღე</a>
      <a class="btn <?= $range==='month'?'active':'' ?>" href="<?= $rangeUrl('month') ?>">ამ თვეში</a>
      <a class="btn <?= $range==='prev_month'?'active':'' ?>" href="<?= $rangeUrl('prev_month') ?>">წინა თვე</a>
      <a class="btn <?= $range==='year'?'active':'' ?>" href="<?= $rangeUrl('year') ?>">ამ წელს</a>
    </div>
  </div>

  <section class="stat-mini-grid">
    <div class="stat-mini"><span>დღეს</span><strong><?= money($todaySales['total']) ?></strong></div>
    <div class="stat-mini"><span>გუშინ</span><strong><?= money($yesterdaySales['total']) ?></strong></div>
    <div class="stat-mini"><span>ამ თვეში</span><strong><?= money($monthSales['total']) ?></strong></div>
    <div class="stat-mini"><span>წინა თვეში</span><strong><?= money($prevMonthSales['total']) ?></strong></div>
  </section>

  <section class="statistics-two">
    <div class="card stat-card">
      <h2>ყველაზე გაყიდვადი პროდუქტები — <?= h($rangeLabel) ?></h2>
      <?php if (!$topProducts): ?>
        <div class="empty-stat">ამ პერიოდზე პროდუქტის გაყიდვა ჯერ არ არის.</div>
      <?php else: ?>
        <div class="stat-table"><table><thead><tr><th class="stat-rank">#</th><th>პროდუქტი</th><th class="stat-num">რაოდ.</th></tr></thead><tbody>
        <?php foreach ($topProducts as $i => $p): ?>
          <tr><td class="stat-rank">#<?= $i + 1 ?></td><td><?= h($p['product_name']) ?></td><td class="stat-num"><?= h(qty($p['qty'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </div>

    <div class="card stat-card">
      <h2>მაგიდების დატვირთვა — <?= h($rangeLabel) ?></h2>
      <?php if (!$tableStats): ?>
        <div class="empty-stat">მაგიდები ვერ მოიძებნა.</div>
      <?php else: ?>
        <div class="stat-table"><table><thead><tr><th>მაგიდა</th><th class="stat-num">ანგარიში</th><th class="stat-num">გაყიდვა</th></tr></thead><tbody>
        <?php foreach ($tableStats as $t): ?>
          <tr><td><?= h($t['table_name']) ?></td><td class="stat-num"><?= (int)$t['orders_count'] ?></td><td class="stat-num"><?= money($t['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </div>
  </section>
</section>
<?php render_footer();
