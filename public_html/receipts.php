<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/receipt-templates.php';
require_admin();

ensure_receipt_templates_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_receipt_templates') {
        $templates = $_POST['templates'] ?? [];
        foreach (['bar','kitchen','final'] as $type) {
            save_receipt_template($type, is_array($templates[$type] ?? null) ? $templates[$type] : []);
        }
        flash('ქვითრების პარამეტრები შეინახა.');
        redirect_to('receipts');
    }
    if ($action === 'reset_receipt_templates') {
        reset_receipt_templates();
        flash('ქვითრების სტანდარტული პარამეტრები აღდგა.');
        redirect_to('receipts');
    }
}

$templates = [
    'bar' => receipt_template('bar'),
    'kitchen' => receipt_template('kitchen'),
    'final' => receipt_template('final'),
];

$sampleTable = ['name' => 'მაგიდა 4'];
$sampleItems = [
    ['quantity'=>2,'product_name'=>'ქალაქური ხინკალი','price'=>1.70,'comment'=>'ერთი უხახვოდ','is_cancelled'=>0],
    ['quantity'=>1,'product_name'=>'ლიმონათი','price'=>4.00,'comment'=>'','is_cancelled'=>0],
];
$sampleOrder = [
    'subtotal_total'=>7.40,'discount_amount'=>0.40,'total'=>7.00,'payment_type'=>'cash','cash_amount'=>7.00,'card_amount'=>0,
];
$previews = [
    'bar' => build_configurable_bar_receipt($sampleTable, $sampleItems, 1042),
    'kitchen' => build_configurable_kitchen_receipt($sampleTable, $sampleItems, 1042),
    'final' => build_configurable_final_receipt($sampleTable, $sampleOrder, $sampleItems, 1042),
];

$labels = [
    'bar' => ['title'=>'სალარო / ბარი','description'=>'შეკვეთის გაგზავნისას პირველი ქვითარი — ფასებით.'],
    'kitchen' => ['title'=>'სამზარეულო','description'=>'შეკვეთის გაგზავნისას მეორე ქვითარი — სტანდარტულად ფასების გარეშე.'],
    'final' => ['title'=>'საბოლოო ანგარიში','description'=>'მაგიდის დახურვისას და ისტორიიდან ხელახლა ბეჭდვისას.'],
];

render_header('ქვითრები');
?>
<style>
.receipt-settings-page{width:min(1280px,100%);margin:0 auto;display:grid;gap:18px}.receipt-settings-head{text-align:center}.receipt-settings-head h1{margin:0}.receipt-settings-head p{max-width:760px;margin:8px auto 0;color:var(--muted);font-weight:800}.receipt-settings-toolbar{display:flex;align-items:center;justify-content:center;gap:9px;flex-wrap:wrap}.receipt-settings-toolbar .btn{min-width:170px}.receipt-editor-list{display:grid;gap:18px}.receipt-editor-card{overflow:hidden;padding:0!important;border-radius:26px!important}.receipt-editor-top{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid rgba(43,27,16,.09);background:linear-gradient(145deg,#fffdf9,#f4e5d2)}.receipt-editor-top h2{margin:0;font-size:1.2rem}.receipt-editor-top p{margin:5px 0 0;color:var(--muted);font-size:.84rem;font-weight:800}.receipt-type-badge{display:inline-flex;align-items:center;justify-content:center;min-width:48px;height:34px;padding:0 11px;border-radius:999px;background:#2b1b10;color:#fff;font-size:.74rem;font-weight:950;text-transform:uppercase}.receipt-editor-body{display:grid;grid-template-columns:minmax(0,1.18fr) minmax(310px,.82fr);gap:18px;padding:18px}.receipt-fields{display:grid;gap:14px}.receipt-fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.receipt-fields textarea{width:100%;min-height:78px;resize:vertical;border:1px solid #c9ad88;border-radius:13px;padding:11px 12px;font:inherit;color:var(--ink);background:#fff}.receipt-number-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.receipt-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.receipt-option{display:flex!important;align-items:center!important;gap:8px!important;min-height:42px;padding:9px 10px;border:1px solid rgba(132,88,48,.15);border-radius:12px;background:rgba(255,255,255,.68);font-size:.78rem!important;font-weight:850!important}.receipt-option input{width:auto!important;min-height:0!important;margin:0}.receipt-preview-wrap{position:sticky;top:78px;align-self:start;padding:15px;border:1px solid rgba(132,88,48,.15);border-radius:20px;background:linear-gradient(160deg,#efe0cd,#f8eee1)}.receipt-preview-label{text-align:center;margin:0 0 10px;font-size:.82rem;color:#6d5140;font-weight:950}.receipt-preview{width:min(80mm,100%);margin:0 auto;padding:14px 12px;border-radius:4px;background:#fff;color:#000;box-shadow:0 13px 28px rgba(43,27,16,.18);overflow:auto}.receipt-preview pre{margin:0;white-space:pre-wrap;word-break:break-word;font-family:Arial,"Noto Sans Georgian",sans-serif;line-height:1.35}.receipt-save-bar{position:sticky;bottom:10px;z-index:5;display:flex;justify-content:center;gap:9px;flex-wrap:wrap;padding:12px;border:1px solid rgba(132,88,48,.16);border-radius:18px;background:rgba(255,250,242,.94);backdrop-filter:blur(10px);box-shadow:0 14px 34px rgba(43,27,16,.13)}.receipt-save-bar .btn{min-width:190px}.receipt-reset-form{display:inline-flex}@media(max-width:980px){.receipt-editor-body{grid-template-columns:1fr}.receipt-preview-wrap{position:static}.receipt-options{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.receipt-settings-page{gap:13px}.receipt-editor-top{align-items:flex-start;padding:15px;flex-direction:column}.receipt-editor-body{padding:13px}.receipt-fields-grid,.receipt-number-grid,.receipt-options{grid-template-columns:1fr}.receipt-save-bar,.receipt-save-bar .btn,.receipt-reset-form{width:100%}.receipt-reset-form .btn{width:100%}}
</style>
<section class="receipt-settings-page">
  <header class="receipt-settings-head">
    <h1>ქვითრების მართვა</h1>
    <p>აქ აკონტროლებ ბარის, სამზარეულოსა და საბოლოო ანგარიშის სათაურებს, ტექსტებს, ინფორმაციის ველებსა და ბეჭდვის ზომას.</p>
  </header>

  <form method="post" id="receipt_settings_form">
    <input type="hidden" name="action" value="save_receipt_templates">
    <div class="receipt-editor-list">
      <?php foreach (['bar','kitchen','final'] as $type): $t=$templates[$type]; ?>
      <section class="card receipt-editor-card" data-receipt-editor="<?= h($type) ?>">
        <div class="receipt-editor-top">
          <div><h2><?= h($labels[$type]['title']) ?></h2><p><?= h($labels[$type]['description']) ?></p></div>
          <span class="receipt-type-badge"><?= h($type) ?></span>
        </div>
        <div class="receipt-editor-body">
          <div class="receipt-fields">
            <div class="receipt-fields-grid">
              <label>ქვითრის სათაური<input name="templates[<?= h($type) ?>][title]" value="<?= h($t['title']) ?>" data-preview-title></label>
              <label>ტექსტის ზომა
                <select name="templates[<?= h($type) ?>][font_size]" data-preview-font>
                  <?php for ($size=10;$size<=18;$size++): ?><option value="<?= $size ?>" <?= (int)$t['font_size']===$size?'selected':'' ?>><?= $size ?> px</option><?php endfor; ?>
                </select>
              </label>
            </div>
            <div class="receipt-number-grid">
              <label>ზედა დამატებითი ტექსტი<textarea name="templates[<?= h($type) ?>][top_note]" placeholder="შეგიძლია რამდენიმე ხაზიც ჩაწერო"><?= h($t['top_note']) ?></textarea></label>
              <label>ქვედა დამატებითი ტექსტი<textarea name="templates[<?= h($type) ?>][bottom_note]" placeholder="მაგ: გმადლობთ სტუმრობისთვის!"><?= h($t['bottom_note']) ?></textarea></label>
            </div>
            <div class="receipt-number-grid">
              <label>გამყოფი ხაზის სიგრძე<input type="number" min="24" max="48" name="templates[<?= h($type) ?>][line_width]" value="<?= (int)$t['line_width'] ?>"></label>
              <div></div>
            </div>
            <div class="receipt-options">
              <?php
              $options=[
                'show_restaurant_name'=>'ქართული სახელი','show_english_name'=>'ინგლისური სახელი','show_address'=>'მისამართი',
                'show_phone'=>'ტელეფონი','show_table'=>'მაგიდა','show_datetime'=>'თარიღი და დრო',
                'show_receipt_number'=>'ქვითრის ნომერი','show_prices'=>'ფასები','show_comments'=>'კომენტარები',
                'show_totals'=>'ჯამები / ფასდაკლება','show_payment'=>'გადახდის ტიპი'
              ];
              foreach ($options as $key=>$label): ?>
                <label class="receipt-option"><input type="checkbox" name="templates[<?= h($type) ?>][<?= h($key) ?>]" value="1" <?= (int)$t[$key]===1?'checked':'' ?>><?= h($label) ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          <aside class="receipt-preview-wrap">
            <p class="receipt-preview-label">შენახული ვერსიის მაგალითი</p>
            <div class="receipt-preview"><pre style="font-size:<?= (int)$t['font_size'] ?>px"><?= h($previews[$type]) ?></pre></div>
          </aside>
        </div>
      </section>
      <?php endforeach; ?>
    </div>
    <div class="receipt-save-bar"><button class="btn success" type="submit">ყველა ცვლილების შენახვა</button><a class="btn" href="<?= h(url_for('tables')) ?>">მაგიდებზე დაბრუნება</a></div>
  </form>

  <form method="post" class="receipt-reset-form" onsubmit="return confirm('ნამდვილად აღვადგინოთ ქვითრების საწყისი პარამეტრები?');">
    <input type="hidden" name="action" value="reset_receipt_templates">
    <button class="btn danger" type="submit">სტანდარტული პარამეტრების აღდგენა</button>
  </form>
</section>
<script>
document.querySelectorAll('[data-receipt-editor]').forEach(function(card){
  const select=card.querySelector('[data-preview-font]');
  const preview=card.querySelector('.receipt-preview pre');
  if(select&&preview) select.addEventListener('change',function(){preview.style.fontSize=select.value+'px';});
});
</script>
<?php render_footer();
