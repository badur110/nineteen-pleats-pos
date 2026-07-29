<?php
require __DIR__ . '/includes/bootstrap.php';

require_login();

$orderId = (int)($_GET['order_id'] ?? 0);
$tableId = (int)($_GET['table_id'] ?? 0);
$ids = array_values(array_filter(array_map('intval', explode(',', $_GET['item_ids'] ?? ''))));

$order = fetch_order($orderId);
$table = fetch_table($tableId);

if (!$order || !$table || !$ids || (int)$order['table_id'] !== $tableId) {
    flash('დასაბეჭდი შეკვეთა ვერ მოიძებნა.', 'warn');
    redirect_to('tables');
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare('SELECT * FROM order_items WHERE order_id=? AND id IN (' . $placeholders . ') ORDER BY id ASC');
$stmt->execute(array_merge([$orderId], $ids));
$items = $stmt->fetchAll();

if (!$items) {
    flash('დასაბეჭდი პროდუქტები ვერ მოიძებნა.', 'warn');
    redirect_to('table', ['id' => $tableId]);
}

$barReceipt = build_cashier_receipt($table, $items);
$kitchenReceipt = build_kitchen_receipt($table, $items);

render_header('შეკვეთის ბეჭდვა');
?>
<style>
.dual-print-page{display:grid;gap:16px}.dual-print-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}.dual-print-head h1{margin:0}.dual-print-note{margin:7px 0 0;color:var(--muted);font-size:.9rem;font-weight:800;max-width:760px}.dual-print-card{max-width:760px;margin:0 auto;width:100%;padding:18px!important}.dual-print-preview{display:grid;gap:0;border:1px solid var(--line);border-radius:18px;overflow:hidden;background:#fff}.dual-receipt-part{padding:18px}.dual-receipt-part h2{margin:0 0 12px;text-align:center;font-size:1.12rem;font-weight:950}.dual-receipt-part pre{margin:0;white-space:pre-wrap;word-break:break-word;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:.88rem;line-height:1.45;color:#111}.dual-cut-line{display:flex;align-items:center;gap:10px;padding:9px 12px;background:#f5e8d6;color:#6d5140;font-size:.82rem;font-weight:950;text-align:center}.dual-cut-line:before,.dual-cut-line:after{content:"";height:1px;flex:1;border-top:1px dashed #6d5140}.dual-print-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px;flex-wrap:wrap}.dual-print-actions .btn{min-height:46px}.dual-print-main{background:#2357a5!important;color:#fff!important}@media(max-width:600px){.dual-print-card{padding:13px!important}.dual-receipt-part{padding:14px}.dual-print-actions,.dual-print-actions .btn{width:100%}.dual-receipt-part pre{font-size:.82rem}}
</style>
<section class="dual-print-page">
  <div class="dual-print-head">
    <div>
      <h1>შეკვეთის ბეჭდვა</h1>
      <p class="dual-print-note">ერთი ღილაკით დაიბეჭდება ჯერ ბარის ქვითარი და შემდეგ სამზარეულოს ქვითარი. შუაში დამატებულია ცალკე გვერდი/ჭრის ადგილი.</p>
    </div>
    <a class="btn" href="<?= h(url_for('table', ['id'=>$tableId])) ?>">მაგიდაზე დაბრუნება</a>
  </div>

  <section class="card dual-print-card">
    <div id="dual_receipt_preview" class="dual-print-preview">
      <article class="dual-receipt-part" data-receipt-title="ბარის ქვითარი">
        <h2>ბარის ქვითარი</h2>
        <pre id="bar_receipt_text"><?= h($barReceipt) ?></pre>
      </article>
      <div class="dual-cut-line">✂ აქ გაიჭრება</div>
      <article class="dual-receipt-part" data-receipt-title="სამზარეულოს ქვითარი">
        <h2>სამზარეულოს ქვითარი</h2>
        <pre id="kitchen_receipt_text"><?= h($kitchenReceipt) ?></pre>
      </article>
    </div>
    <div class="dual-print-actions">
      <button type="button" class="btn primary dual-print-main" id="print_both_receipts">ორივე ქვითრის ბეჭდვა</button>
    </div>
  </section>
</section>
<script>
(function () {
  const button = document.getElementById('print_both_receipts');
  if (!button) return;

  function escapePrintHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }

  button.addEventListener('click', function () {
    const bar = document.getElementById('bar_receipt_text');
    const kitchen = document.getElementById('kitchen_receipt_text');
    if (!bar || !kitchen) return;

    const printWindow = window.open('', '_blank', 'width=460,height=760');
    if (!printWindow) {
      alert('ბრაუზერმა ბეჭდვის ფანჯარა დაბლოკა. დაუშვი pop-up ამ საიტისთვის.');
      return;
    }

    const barText = escapePrintHtml(bar.innerText);
    const kitchenText = escapePrintHtml(kitchen.innerText);

    printWindow.document.write('<!doctype html><html lang="ka"><head><meta charset="utf-8"><title>ბარი და სამზარეულო</title><style>' +
      '@page{size:80mm auto;margin:3mm}' +
      'html,body{margin:0;padding:0;background:#fff;color:#000}' +
      'body{width:74mm;font-family:Arial,"Noto Sans Georgian",sans-serif}' +
      '.receipt-page{box-sizing:border-box;width:100%;padding:0 0 3mm;break-after:page;page-break-after:always}' +
      '.receipt-page:last-child{break-after:auto;page-break-after:auto}' +
      'h1{margin:0 0 4mm;text-align:center;font-size:18px;line-height:1.2;font-weight:900}' +
      'pre{margin:0;white-space:pre-wrap;word-break:break-word;font-family:Arial,"Noto Sans Georgian",sans-serif;font-size:13px;line-height:1.35}' +
      '.cut{margin-top:5mm;padding-top:2mm;border-top:1px dashed #000;text-align:center;font-size:11px;font-weight:700}' +
      '</style></head><body>' +
      '<section class="receipt-page"><h1>ბარის ქვითარი</h1><pre>' + barText + '</pre><div class="cut">✂ აქ გაიჭრება</div></section>' +
      '<section class="receipt-page"><h1>სამზარეულოს ქვითარი</h1><pre>' + kitchenText + '</pre></section>' +
      '<script>window.onload=function(){window.print();};window.onafterprint=function(){window.close();};<\/script>' +
      '</body></html>');
    printWindow.document.close();
  });
})();
</script>
<?php render_footer();
