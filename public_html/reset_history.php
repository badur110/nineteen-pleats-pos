<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/order-numbers.php';

require_admin();

function history_reset_counts(): array {
    $tables = [
        'business_days' => 'სამუშაო დღეები',
        'orders' => 'შეკვეთები',
        'order_items' => 'გაყიდული პროდუქტები',
        'cash_movements' => 'სალაროს მოძრაობები',
        'order_number_sequence' => 'ქვითრის ნომრები',
    ];
    $result = [];
    foreach ($tables as $table => $label) {
        try {
            $result[$table] = ['label' => $label, 'count' => (int)db()->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn()];
        } catch (Throwable $e) {
            $result[$table] = ['label' => $label, 'count' => 0];
        }
    }
    return $result;
}

if (empty($_SESSION['history_reset_token'])) {
    $_SESSION['history_reset_token'] = bin2hex(random_bytes(24));
}

$error = '';
$counts = history_reset_counts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['token'] ?? '');
    $confirmation = trim((string)($_POST['confirmation'] ?? ''));

    if (!hash_equals((string)$_SESSION['history_reset_token'], $token)) {
        $error = 'უსაფრთხოების კოდი არასწორია. განაახლე გვერდი და სცადე თავიდან.';
    } elseif ($confirmation !== 'განულება') {
        $error = 'დასადასტურებლად ზუსტად ჩაწერე სიტყვა „განულება“.';
    } else {
        $pdo = db();
        $locked = false;
        try {
            $lock = $pdo->prepare('SELECT GET_LOCK(?, 15)');
            $lock->execute(['garbalia_full_history_reset']);
            $locked = (int)$lock->fetchColumn() === 1;
            if (!$locked) {
                throw new RuntimeException('Reset lock unavailable.');
            }

            ensure_order_numbering();
            $deleted = $counts;

            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM order_items');
            $pdo->exec('DELETE FROM orders');
            $pdo->exec('DELETE FROM cash_movements');
            $pdo->exec('DELETE FROM business_days');
            $pdo->exec('DELETE FROM order_number_sequence');
            $pdo->commit();

            $pdo->exec('ALTER TABLE order_items AUTO_INCREMENT=1');
            $pdo->exec('ALTER TABLE orders AUTO_INCREMENT=1');
            $pdo->exec('ALTER TABLE cash_movements AUTO_INCREMENT=1');
            $pdo->exec('ALTER TABLE business_days AUTO_INCREMENT=1');
            $pdo->exec('ALTER TABLE order_number_sequence AUTO_INCREMENT=1000');

            $_SESSION['history_reset_token'] = bin2hex(random_bytes(24));
            $totalOrders = (int)($deleted['orders']['count'] ?? 0);
            $totalItems = (int)($deleted['order_items']['count'] ?? 0);
            flash('ისტორია სრულად განულდა: წაიშალა ' . $totalOrders . ' შეკვეთა და ' . $totalItems . ' გაყიდული პროდუქტი. შემდეგი ქვითარი დაიწყება #1000-დან.');
            redirect_to('history');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'განულება ვერ დასრულდა. მონაცემები უსაფრთხოდ შევაჩერე — სცადე თავიდან ან შეამოწმე ბაზის უფლებები.';
        } finally {
            if ($locked) {
                try {
                    $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                    $release->execute(['garbalia_full_history_reset']);
                } catch (Throwable $ignored) {
                }
            }
        }
    }
}

render_header('ისტორიის განულება');
?>
<style>
.history-reset-page{width:min(720px,100%);min-height:calc(100vh - 210px);margin:0 auto;display:grid;place-items:center;padding:24px 0}.history-reset-card{width:100%;padding:28px!important;border-radius:28px!important;text-align:center;box-shadow:0 22px 55px rgba(43,27,16,.14)!important}.history-reset-icon{display:grid;place-items:center;width:62px;height:62px;margin:0 auto 14px;border-radius:20px;background:#fff0ed;color:#b9332a;font-size:1.7rem;font-weight:950}.history-reset-card h1{margin:0;font-size:1.8rem}.history-reset-lead{max-width:600px;margin:10px auto 18px;color:var(--muted);font-weight:800;line-height:1.55}.history-reset-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin:18px 0;text-align:left}.history-reset-summary div{display:flex;justify-content:space-between;gap:12px;padding:12px 13px;border:1px solid rgba(43,27,16,.09);border-radius:14px;background:rgba(255,255,255,.66)}.history-reset-summary span{color:#766252;font-weight:850}.history-reset-summary strong{font-weight:950}.history-reset-preserved{margin:16px 0;padding:13px 15px;border-radius:15px;background:#ebf8ed;color:#24683a;font-weight:850;line-height:1.45}.history-reset-error{margin:14px 0;padding:12px 14px;border-radius:14px;background:#fff0ed;color:#a72f28;font-weight:900}.history-reset-form{display:grid;gap:12px;margin-top:18px;text-align:left}.history-reset-form label{font-weight:900}.history-reset-form input{min-height:48px!important;text-align:center;font-weight:900;letter-spacing:.02em}.history-reset-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:3px}.history-reset-actions .btn{width:100%;min-height:48px}.history-reset-warning{margin:0;color:#a72f28;font-size:.82rem;font-weight:850;text-align:center}@media(max-width:620px){.history-reset-page{padding:10px 0;min-height:auto}.history-reset-card{padding:22px 16px!important;border-radius:22px!important}.history-reset-summary,.history-reset-actions{grid-template-columns:1fr}}
</style>
<section class="history-reset-page">
  <div class="card history-reset-card">
    <div class="history-reset-icon">↺</div>
    <h1>ისტორიის სრულად განულება</h1>
    <p class="history-reset-lead">ეს მოქმედება წაშლის ყველა სამუშაო დღეს, შეკვეთას, გაყიდულ პროდუქტს და სალაროს მოძრაობას. ქვითრების ახალი ათვლა დაიწყება <strong>#1000</strong>-დან.</p>

    <div class="history-reset-summary">
      <?php foreach ($counts as $item): ?>
        <div><span><?= h($item['label']) ?></span><strong><?= (int)$item['count'] ?></strong></div>
      <?php endforeach; ?>
    </div>

    <div class="history-reset-preserved">დარჩება უცვლელი: პროდუქტები და თვითღირებულებები, კატეგორიები, მაგიდები, მომხმარებლები და ქვითრების დიზაინის პარამეტრები.</div>

    <?php if ($error !== ''): ?><div class="history-reset-error"><?= h($error) ?></div><?php endif; ?>

    <form class="history-reset-form" method="post" autocomplete="off">
      <input type="hidden" name="token" value="<?= h($_SESSION['history_reset_token']) ?>">
      <label>დასადასტურებლად ჩაწერე: <strong>განულება</strong>
        <input name="confirmation" required placeholder="განულება" inputmode="text">
      </label>
      <p class="history-reset-warning">მოქმედება შეუქცევადია და წაშლილი ისტორიის აღდგენა ამ გვერდიდან შეუძლებელია.</p>
      <div class="history-reset-actions">
        <a class="btn light" href="<?= h(url_for('history')) ?>">უკან დაბრუნება</a>
        <button class="btn danger" type="submit">ყველაფრის განულება</button>
      </div>
    </form>
  </div>
</section>
<?php render_footer();
