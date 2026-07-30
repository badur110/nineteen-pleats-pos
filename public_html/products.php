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

/* Desktop: two product cards on each row */
@media(min-width:1100px){
  body.app-shell.page-products .product-list-card .table-wrap{overflow:visible!important;border:0!important;background:transparent!important}
  body.app-shell.page-products .product-list-card table{display:block!important;width:100%!important;min-width:0!important;background:transparent!important}
  body.app-shell.page-products .product-list-card tbody{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px!important;width:100%!important}
  body.app-shell.page-products .product-list-card tbody>tr{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-rows:auto auto auto!important;gap:6px 12px!important;align-items:center!important;width:100%!important;min-width:0!important;padding:12px 13px!important;border-radius:15px!important;overflow:visible!important}
  body.app-shell.page-products .product-list-card tbody>tr>td{min-width:0!important;padding:0!important;border:0!important;overflow:visible!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(1){grid-column:1/2!important;grid-row:1!important;font-size:.90rem!important;font-weight:950!important;white-space:normal!important;overflow-wrap:anywhere!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(2){grid-column:1/2!important;grid-row:2!important;font-size:.76rem!important;color:#7a6657!important;white-space:normal!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(3){grid-column:2/3!important;grid-row:1/3!important;align-self:center!important;justify-self:end!important;text-align:right!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(4){grid-column:2/3!important;grid-row:3!important;justify-self:end!important;width:auto!important;min-width:74px!important;padding:5px 8px!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5){grid-column:1/2!important;grid-row:3!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:7px!important;flex-wrap:wrap!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5) form{margin:0!important;display:inline-flex!important}
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5) .btn,
  body.app-shell.page-products .product-list-card tbody>tr>td:nth-child(5) a{min-height:32px!important;padding:6px 9px!important;font-size:.75rem!important;border-radius:9px!important;white-space:nowrap!important}
  body.app-shell.page-products .product-finance-cell strong{font-size:.82rem!important}
  body.app-shell.page-products .product-finance-cell small{font-size:.69rem!important;line-height:1.18!important}
}

@media(max-width:1120px){.page-products .product-add-card form.cost-product-form{grid-template-columns:1fr 1fr 1fr!important}.page-products .product-add-card form.cost-product-form .check,.page-products .product-add-card form.cost-product-form .btn{grid-column:auto!important}}
@media(max-width:1099px){body.app-shell.page-products .product-list-card tbody{grid-template-columns:1fr!important}}
@media(max-width:820px){.page-products .product-add-card form.cost-product-form{grid-template-columns:1fr 1fr!important}.page-products .product-add-card form.cost-product-form .btn{grid-column:1/-1!important}}
@media(max-width:640px){.page-products .product-add-card form.cost-product-form{grid-template-columns:1fr!important}.product-finance-cell{display:flex!important;flex-direction:column!important;align-items:flex-end!important}.product-finance-cell:before{align-self:flex-start}}
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
<?php render_footer();
