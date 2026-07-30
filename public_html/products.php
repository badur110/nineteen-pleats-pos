<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/product-cost.php';

require_admin();
ensure_product_cost_schema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_product') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = max(0, (float)($_POST['price'] ?? 0));
        $cost = max(0, (float)($_POST['cost'] ?? 0));
        $categoryName = trim($_POST['category_name'] ?? 'სხვა');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            flash('პროდუქტის სახელი აუცილებელია.', 'warn');
            redirect_to('products', $id > 0 ? ['edit' => $id] : []);
        }
        if ($categoryName === '') $categoryName = 'სხვა';

        $stmt = db()->prepare('SELECT id FROM categories WHERE name=? LIMIT 1');
        $stmt->execute([$categoryName]);
        $categoryId = $stmt->fetchColumn();
        if (!$categoryId) {
            db()->prepare('INSERT INTO categories (name, is_active) VALUES (?, 1)')->execute([$categoryName]);
            $categoryId = db()->lastInsertId();
        }

        if ($id > 0) {
            $stmt = db()->prepare('UPDATE products SET category_id=?, name=?, price=?, cost=?, is_active=? WHERE id=?');
            $stmt->execute([$categoryId, $name, $price, $cost, $isActive, $id]);
            flash('პროდუქტი და თვითღირებულება განახლდა.');
        } else {
            $stmt = db()->prepare('INSERT INTO products (category_id, name, price, cost, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$categoryId, $name, $price, $cost, $isActive]);
            flash('პროდუქტი დაემატა.');
        }
        redirect_to('products');
    }

    if ($action === 'toggle_product') {
        $id = (int)($_POST['id'] ?? 0);
        $active = (int)($_POST['is_active'] ?? 0);
        db()->prepare('UPDATE products SET is_active=? WHERE id=?')->execute([$active, $id]);
        flash($active ? 'პროდუქტი გააქტიურდა.' : 'პროდუქტი გაითიშა.');
        redirect_to('products');
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$products = db()->query('SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.is_active DESC, CASE WHEN c.name LIKE "%ხინკ%" THEN 0 WHEN c.name LIKE "%სასმ%" THEN 1 ELSE 2 END, c.sort_order, c.name, p.sort_order, p.name')->fetchAll();

render_header('პროდუქტები');
?>
<style>
.page-products .product-add-card form.cost-product-form{grid-template-columns:minmax(220px,1.35fr) 140px 150px minmax(180px,.9fr) 125px 160px!important}
.product-finance-cell{display:grid!important;gap:3px!important;white-space:normal!important}
.product-finance-cell strong{font-size:.95rem;color:#2b1b10}
.product-finance-cell small{display:block;color:#7a6657;font-size:.76rem;font-weight:800;line-height:1.25}
.product-finance-cell .unit-profit{color:#24733c;font-weight:950}
.product-finance-cell .unit-profit.negative{color:#b9332a}

/* Product cards: 3 columns on wide desktop, 2 on medium, 1 on small screens */
@media(min-width:900px){
  body.app-shell.page-products .product-list-card .table-wrap{overflow:visible!important;border:0!important;background:transparent!important}
  body.app-shell.page-products .product-list-card table{display:block!important;width:100%!important;min-width:0!important;background:transparent!important}
  body.app-shell.page-products .product-list-card tbody{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:9px!important;width:100%!important}
  body.app-shell.page-products .product-list-card tbody>tr{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-rows:auto auto auto!important;gap:5px 10px!important;align-items:center!important;width:100%!important;min-width:0!important;padding:11px 12px!important;border-radius:14px!important;overflow:visible!important}
  body.app-shell.page-products .product-list-card tbody>tr>td{min-width:0!important;padding:0!important;border:0!important;overflow:visible!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(1){grid-column:1/2!important;grid-row:1!important;font-size:.86rem!important;font-weight:950!important;white-space:normal!important;overflow-wrap:anywhere!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(2){grid-column:1/2!important;grid-row:2!important;font-size:.72rem!important;color:#7a6657!important;white-space:normal!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(3){grid-column:2/3!important;grid-row:1/3!important;align-self:center!important;justify-self:end!important;text-align:right!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(4){grid-column:2/3!important;grid-row:3!important;justify-self:end!important;width:auto!important;min-width:66px!important;padding:4px 7px!important;font-size:.70rem!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5){grid-column:1/2!important;grid-row:3!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:6px!important;flex-wrap:wrap!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5) form{margin:0!important;display:inline-flex!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5) .btn,
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5) a{min-height:30px!important;padding:5px 8px!important;font-size:.71rem!important;border-radius:8px!important;white-space:nowrap!important}
  body.app-shell.page-products .product-finance-cell strong{font-size:.77rem!important}
  body.app-shell.page-products .product-finance-cell small{font-size:.64rem!important;line-height:1.16!important}
}
@media(min-width:1250px){body.app-shell.page-products .product-list-card tbody{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:1120px){.page-products .product-add-card form.cost-product-form{grid-template-columns:1fr 1fr 1fr!important}.page-products .product-add-card form.cost-product-form .check,.page-products .product-add-card form.cost-product-form .btn{grid-column:auto!important}}
@media(max-width:899px){body.app-shell.page-products .product-list-card tbody{grid-template-columns:1fr!important}}
@media(max-width:820px){.page-products .product-add-card form.cost-product-form{grid-template-columns:1fr 1fr!important}.page-products .product-add-card form.cost-product-form .btn{grid-column:1/-1!important}}
@media(max-width:640px){.page-products .product-add-card form.cost-product-form{grid-template-columns:1fr!important}.product-finance-cell{display:flex!important;flex-direction:column!important;align-items:flex-end!important}.product-finance-cell:before{align-self:flex-start}}

/* Premium product editor modal */
.garbalia-product-modal[hidden]{display:none!important}
.garbalia-product-modal{position:fixed;inset:0;z-index:11000;display:grid;place-items:center;padding:18px;background:rgba(43,27,16,.48);backdrop-filter:blur(8px);opacity:0;transition:opacity .16s ease}
.garbalia-product-modal.is-open{opacity:1}
.garbalia-product-dialog{position:relative;width:min(660px,calc(100vw - 28px));max-height:calc(100dvh - 28px);overflow-y:auto;overflow-x:hidden;border:1px solid #e8d2b5;border-radius:28px;background:linear-gradient(180deg,#fffaf2 0%,#f7ead9 100%);box-shadow:0 30px 80px rgba(43,27,16,.34);padding:24px}
.garbalia-product-dialog:before{content:"";position:absolute;right:-38px;top:-38px;width:190px;height:150px;background:url('/Logo.png?v=12') center/contain no-repeat;opacity:.055;filter:brightness(0);pointer-events:none}
.garbalia-product-close{position:absolute;right:14px;top:14px;z-index:2;width:36px;height:36px;border:0;border-radius:50%;background:rgba(43,27,16,.09);color:#2b1b10;font-size:22px;font-weight:950;cursor:pointer}
.garbalia-product-modal-head{display:flex;align-items:center;gap:13px;padding-right:42px;margin-bottom:18px}
.garbalia-product-modal-logo{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:15px;background:#2b1b10;box-shadow:0 10px 24px rgba(43,27,16,.18)}
.garbalia-product-modal-logo img{width:34px;height:29px;object-fit:contain;filter:brightness(0) invert(1)}
.garbalia-product-modal-head h3{margin:0;font-size:1.28rem;font-weight:950;letter-spacing:-.02em;color:#2b1b10}
.garbalia-product-modal-head p{margin:4px 0 0;color:#7a6657;font-size:.86rem;font-weight:800}
.garbalia-product-form-slot .product-add-card{display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important}
.garbalia-product-form-slot .product-add-card>h2{display:none!important}
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form{display:grid!important;grid-template-columns:1fr 1fr!important;gap:12px!important;align-items:end!important}
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form label{margin:0!important;padding:12px;border:1px solid rgba(43,27,16,.10);border-radius:15px;background:rgba(255,255,255,.72);font-size:.84rem!important}
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form label:nth-of-type(1),
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form label:nth-of-type(4){grid-column:1/-1!important}
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form .check{grid-column:1/2!important;min-height:50px!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:9px!important}
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form .check input{width:auto!important}
body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form>.btn{grid-column:2/3!important;min-height:50px!important;border-radius:14px!important;font-size:.92rem!important}
body.garbalia-modal-open{overflow:hidden!important}
@media(max-width:620px){
  .garbalia-product-modal{padding:8px}
  .garbalia-product-dialog{width:calc(100vw - 16px);max-height:calc(100dvh - 16px);padding:20px 15px 15px;border-radius:23px}
  .garbalia-product-modal-head{align-items:flex-start}
  body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form{grid-template-columns:1fr!important}
  body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form label,
  body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form .check,
  body.app-shell.page-products .garbalia-product-dialog .product-add-card form.cost-product-form>.btn{grid-column:1!important}
}
</style>
<div class="page-head"><h1>პროდუქტები</h1></div>
<section class="two-col">
  <div class="card">
    <h2><?= $edit ? 'რედაქტირება' : 'ახალი პროდუქტი' ?></h2>
    <form class="stack cost-product-form" method="post">
      <input type="hidden" name="action" value="save_product">
      <input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>">
      <label>სახელი<input name="name" required value="<?= h($edit['name'] ?? '') ?>"></label>
      <label>გასაყიდი ფასი<input name="price" type="number" step="0.01" min="0" required value="<?= h($edit['price'] ?? '') ?>"></label>
      <label>თვითღირებულება<input name="cost" type="number" step="0.01" min="0" required value="<?= h($edit['cost'] ?? '0.00') ?>"></label>
      <label>კატეგორია<input name="category_name" value="<?= h($edit['category_name'] ?? 'სხვა') ?>"></label>
      <label class="check"><input type="checkbox" name="is_active" <?= !$edit || (int)$edit['is_active'] === 1 ? 'checked' : '' ?>> აქტიური</label>
      <button class="btn success">შენახვა</button>
    </form>
  </div>

  <div class="card">
    <h2>პროდუქტების სია</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>პროდუქტი</th><th>კატეგორია</th><th>ფასი / თვითღირებულება</th><th>სტატუსი</th><th>ქმედება</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p):
          $profit = product_unit_profit($p);
          $margin = product_margin_percent($p);
        ?>
          <tr>
            <td><?= h($p['name']) ?></td>
            <td><?= h($p['category_name']) ?></td>
            <td class="product-finance-cell">
              <strong>ფასი: <?= money($p['price']) ?></strong>
              <small>თვითღირებულება: <?= money($p['cost']) ?></small>
              <small class="unit-profit <?= $profit < 0 ? 'negative' : '' ?>">მოგება / ც.: <?= money($profit) ?> · <?= h(number_format($margin, 1)) ?>%</small>
            </td>
            <td><?= (int)$p['is_active'] ? 'აქტიური' : 'გათიშული' ?></td>
            <td>
              <a href="<?= h(url_for('products', ['edit'=>(int)$p['id']])) ?>">რედაქტირება</a>
              <form method="post" style="margin-top:8px">
                <input type="hidden" name="action" value="toggle_product">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="is_active" value="<?= (int)$p['is_active'] ? 0 : 1 ?>">
                <button class="btn <?= (int)$p['is_active'] ? 'danger' : 'success' ?>"><?= (int)$p['is_active'] ? 'გათიშვა' : 'ჩართვა' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<script>
(function () {
  function setupProductModal() {
    const addCard = document.querySelector('.product-add-card') || document.querySelector('.two-col > .card:first-child');
    const panel = document.querySelector('.product-top-panel');
    if (!addCard || !panel || document.querySelector('.garbalia-product-modal')) return;

    const editMode = new URLSearchParams(window.location.search).has('edit');
    const oldToggle = panel.querySelector('.product-add-toggle');
    if (!oldToggle) return;

    const toggle = oldToggle.cloneNode(true);
    oldToggle.replaceWith(toggle);
    toggle.textContent = editMode ? 'პროდუქტის რედაქტირება' : 'ახალი პროდუქტის დამატება';

    const holder = panel.querySelector('.product-add-holder');
    if (holder) holder.style.display = 'none';

    const modal = document.createElement('div');
    modal.className = 'garbalia-product-modal';
    modal.hidden = true;
    modal.innerHTML = '<section class="garbalia-product-dialog" role="dialog" aria-modal="true" aria-labelledby="garbalia-product-title">' +
      '<button type="button" class="garbalia-product-close" aria-label="დახურვა">×</button>' +
      '<div class="garbalia-product-modal-head"><span class="garbalia-product-modal-logo"><img src="/Logo.png?v=12" alt=""></span><div><h3 id="garbalia-product-title">' +
      (editMode ? 'პროდუქტის რედაქტირება' : 'ახალი პროდუქტის დამატება') +
      '</h3><p>' + (editMode ? 'შეცვალე პროდუქტის მონაცემები და შეინახე.' : 'შეავსე პროდუქტის ძირითადი მონაცემები.') +
      '</p></div></div><div class="garbalia-product-form-slot"></div></section>';

    document.body.appendChild(modal);
    modal.querySelector('.garbalia-product-form-slot').appendChild(addCard);
    addCard.hidden = false;

    function openModal() {
      modal.hidden = false;
      addCard.hidden = false;
      document.body.classList.add('garbalia-modal-open');
      window.requestAnimationFrame(function () {
        modal.classList.add('is-open');
        const firstInput = addCard.querySelector('input[name="name"]');
        if (firstInput) firstInput.focus();
      });
    }

    function closeModal() {
      if (editMode) {
        window.location.href = '/products';
        return;
      }
      modal.classList.remove('is-open');
      document.body.classList.remove('garbalia-modal-open');
      window.setTimeout(function () { modal.hidden = true; }, 160);
    }

    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      openModal();
    });
    modal.querySelector('.garbalia-product-close').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    if (editMode) openModal();
  }

  window.addEventListener('load', setupProductModal);
})();
</script>
<?php render_footer();
