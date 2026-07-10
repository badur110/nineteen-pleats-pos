<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/actions.php';

function setup_page(): void {
    render_header('Setup');
    echo '<section class="card narrow"><h1>კონფიგურაცია აკლია</h1><p class="muted">ჰოსტინგზე ატვირთვის შემდეგ public_html-ში დააკოპირე <b>config.example.php</b> ფაილი სახელით <b>config.php</b> და ჩაწერე MySQL ბაზის მონაცემები.</p><div class="flash">შემდეგ phpMyAdmin-ში დააიმპორტე database/schema.sql.</div></section>';
    render_footer();
}

function page_login(): void {
    if (is_logged_in()) redirect_to('day');
    render_header('შესვლა');
    echo '<section class="login-card"><div class="login-logo">19</div><h1>შესვლა</h1><form class="stack" method="post"><input type="hidden" name="action" value="login"><label>მომხმარებელი<input name="username" autocomplete="username" required></label><label>პაროლი<input name="password" type="password" autocomplete="current-password" required></label><button class="btn primary">შესვლა</button></form><p class="hint">საწყისი Admin: admin / admin123<br>საწყისი მოლარე: cashier / cashier123</p></section>';
    render_footer();
}

function page_day(): void {
    require_login();
    $day = active_day();
    render_header('სამუშაო დღე');
    if (!$day) {
        echo '<section class="card narrow"><h1>სამუშაო დღის გახსნა</h1><p class="muted">შეკვეთების მიღებამდე გახსენი დღე და ჩაწერე სალაროში არსებული საწყისი ნაღდი თანხა.</p><form class="stack" method="post"><input type="hidden" name="action" value="open_day"><label>საწყისი ნაღდი თანხა<input type="number" step="0.01" min="0" name="opening_cash" value="0"></label><label>კომენტარი<input name="note" placeholder="არასავალდებულო"></label><button class="btn success">დღის გახსნა</button></form></section>';
        render_footer();
        return;
    }
    $summary = day_summary((int)$day['id']);
    $openOrders = open_orders_count((int)$day['id']);
    $expectedCash = (float)$day['opening_cash'] + (float)$summary['cash_total'];
    echo '<div class="page-head"><div><h1>დღე გახსნილია</h1><p class="muted">გახსნა: '.h($day['opened_at']).'</p></div><a class="btn primary" href="'.h(url_for('tables')).'">მაგიდებზე გადასვლა</a></div>';
    echo '<section class="stats"><div><span>გაყიდვები</span><strong>'.money($summary['sales_total']).'</strong></div><div><span>ნაღდი</span><strong>'.money($summary['cash_total']).'</strong></div><div><span>ბარათი</span><strong>'.money($summary['card_total']).'</strong></div><div><span>ღია მაგიდები</span><strong>'.$openOrders.'</strong></div><div><span>მოსალოდნელი ნაღდი</span><strong>'.money($expectedCash).'</strong></div></section>';
    echo '<section class="card narrow"><h2>დღის დახურვა</h2>';
    if ($openOrders > 0) echo '<div class="warn">დღის დახურვამდე ყველა ღია მაგიდა უნდა დაიხუროს.</div>';
    echo '<form class="stack" method="post"><input type="hidden" name="action" value="close_day"><label>რეალურად დათვლილი ნაღდი<input type="number" step="0.01" min="0" name="closing_cash" value="'.h(number_format($expectedCash,2,'.','')).'"></label><label>დახურვის კომენტარი<input name="close_note" placeholder="არასავალდებულო"></label><button class="btn danger" '.($openOrders>0?'disabled':'').'>დღის დახურვა</button></form></section>';
    render_footer();
}

function page_tables(): void {
    require_login();
    $day = active_day();
    if (!$day) { flash('ჯერ გახსენი სამუშაო დღე.', 'warn'); redirect_to('day'); }
    render_header('მაგიდები');
    echo '<div class="page-head"><h1>მაგიდები</h1><span class="pill">დღე #'.(int)$day['id'].'</span></div><div class="tables-grid">';
    $tables = db()->query('SELECT * FROM restaurant_tables WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
    foreach ($tables as $table) {
        $order = current_open_order((int)$day['id'], (int)$table['id']);
        $total = $order ? order_total((int)$order['id']) : 0;
        $class = $order ? 'occupied' : 'free';
        echo '<a class="table-card '.$class.'" href="'.h(url_for('table', ['id'=>(int)$table['id']])).'"><span>'.h($table['name']).'</span><strong>'.($order ? money($total) : 'თავისუფალი').'</strong></a>';
    }
    echo '</div>';
    render_footer();
}

function page_table(): void {
    require_login();
    $day = active_day();
    if (!$day) { flash('ჯერ გახსენი სამუშაო დღე.', 'warn'); redirect_to('day'); }
    $tableId = (int)($_GET['id'] ?? 0);
    $table = fetch_table($tableId);
    if (!$table) redirect_to('tables');
    $order = current_open_order((int)$day['id'], $tableId);
    $items = $order ? order_items((int)$order['id']) : [];
    $total = $order ? order_total((int)$order['id']) : 0;
    $products = db()->query('SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 ORDER BY c.sort_order, c.name, p.sort_order, p.name')->fetchAll();
    render_header($table['name']);
    echo '<div class="page-head"><h1>'.h($table['name']).'</h1><div class="total-box">'.money($total).'</div></div><section class="pos-grid"><div class="card"><h2>პროდუქტის დამატება</h2>';
    if (!$products) echo '<p class="muted">პროდუქტები ჯერ არ არის დამატებული.</p>';
    $cat = null;
    foreach ($products as $product) {
        if ($cat !== $product['category_name']) { $cat = $product['category_name']; echo '<h3 class="category-title">'.h($cat ?: 'სხვა').'</h3>'; }
        echo '<form class="product-row" method="post"><input type="hidden" name="action" value="add_item"><input type="hidden" name="table_id" value="'.$tableId.'"><input type="hidden" name="product_id" value="'.(int)$product['id'].'"><div class="product-name"><strong>'.h($product['name']).'</strong><small>'.money($product['price']).'</small></div><input class="qty-input" name="quantity" type="number" min="1" step="1" value="1"><input name="comment" placeholder="კომენტარი"><button class="btn">დამატება</button></form>';
    }
    echo '</div><div class="card"><h2>მიმდინარე შეკვეთა</h2>';
    if (!$items) echo '<p class="muted">შეკვეთა ჯერ ცარიელია.</p>';
    foreach ($items as $item) {
        $cancelled = (int)$item['is_cancelled'] === 1;
        echo '<div class="order-item '.($cancelled?'cancelled':'').'"><div><strong>'.h(qty($item['quantity']).' x '.$item['product_name']).'</strong><small>'.money($item['price']).' / ჯამი: '.money((float)$item['price']*(float)$item['quantity']).'</small>';
        if ($item['comment']) echo '<em>'.h($item['comment']).'</em>';
        if ($item['sent_at']) echo '<small class="sent">გაგზავნილია</small>';
        if ($cancelled) echo '<small class="danger-text">გაუქმებულია: '.h($item['cancel_reason']).'</small>';
        echo '</div>';
        if (!$cancelled) {
            echo '<form class="cancel-form" method="post"><input type="hidden" name="action" value="cancel_item"><input type="hidden" name="table_id" value="'.$tableId.'"><input type="hidden" name="item_id" value="'.(int)$item['id'].'">';
            if (!is_admin()) echo '<input type="password" name="cancel_password" placeholder="პაროლი">'; else echo '<span class="muted">Admin</span>';
            echo '<select name="cancel_reason"><option>შეცდომით დაემატა</option><option>კლიენტმა გადაიფიქრა</option><option>პროდუქტი აღარ არის</option><option>სხვა</option></select><input name="cancel_reason_custom" placeholder="დამატებით"><button class="btn danger">გაუქმება</button></form>';
        }
        echo '</div>';
    }
    echo '<div class="actions"><form method="post"><input type="hidden" name="action" value="send_order"><input type="hidden" name="table_id" value="'.$tableId.'"><button class="btn primary" '.(!$items?'disabled':'').'>შეკვეთის გაგზავნა / ბეჭდვა</button></form></div>';
    if ($order) {
        echo '<hr><h2>მაგიდის დახურვა</h2><form class="close-form" method="post"><input type="hidden" name="action" value="close_order"><input type="hidden" name="table_id" value="'.$tableId.'"><label>გადახდის ტიპი<select id="payment_type" name="payment_type"><option value="cash">ნაღდი</option><option value="card">ბარათი</option><option value="mixed">შერეული</option></select></label><div id="mixed_fields" class="mixed-fields"><label>ნაღდი<input name="cash_amount" type="number" step="0.01" min="0"></label><label>ბარათი<input name="card_amount" type="number" step="0.01" min="0"></label></div><button class="btn success">საბოლოო ანგარიში</button></form>';
    }
    echo '</div></section>';
    render_footer();
}

function page_print_order(): void {
    require_login();
    $orderId = (int)($_GET['order_id'] ?? 0);
    $tableId = (int)($_GET['table_id'] ?? 0);
    $ids = array_values(array_filter(array_map('intval', explode(',', $_GET['item_ids'] ?? ''))));
    $order = fetch_order($orderId); $table = fetch_table($tableId);
    if (!$order || !$table || !$ids) redirect_to('tables');
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id=? AND id IN ('.implode(',', array_fill(0,count($ids),'?')).') ORDER BY id ASC');
    $stmt->execute(array_merge([$orderId], $ids));
    $items = $stmt->fetchAll();
    render_header('შეკვეთის ბეჭდვა');
    echo '<div class="page-head"><h1>შეკვეთის ბეჭდვა</h1><a class="btn" href="'.h(url_for('table',['id'=>$tableId])).'">მაგიდაზე დაბრუნება</a></div><p class="muted">ჯერ დაბეჭდე სალარო/ბარი, შემდეგ სამზარეულო. Print ფანჯარაში აირჩიე შესაბამისი პრინტერი.</p><section class="print-grid">';
    echo receipt_card('cashier_receipt', 'სალარო / ბარი — ფასებით', build_cashier_receipt($table, $items));
    echo receipt_card('kitchen_receipt', 'სამზარეულო — ფასების გარეშე', build_kitchen_receipt($table, $items));
    echo '</section>';
    render_footer();
}

function page_print_final(): void {
    require_login();
    $orderId = (int)($_GET['order_id'] ?? 0);
    $order = fetch_order($orderId);
    if (!$order) redirect_to('tables');
    $table = fetch_table((int)$order['table_id']);
    if (!$table) redirect_to('tables');
    $items = order_items($orderId);
    render_header('საბოლოო ანგარიში');
    echo '<div class="page-head"><h1>საბოლოო ანგარიში</h1><a class="btn" href="'.h(url_for('tables')).'">მაგიდებზე დაბრუნება</a></div><section class="print-grid single">';
    echo receipt_card('final_receipt', 'სალაროს საბოლოო ანგარიში', build_final_receipt($table, $order, $items));
    echo '</section>';
    render_footer();
}

function page_products(): void {
    require_admin();
    $edit = null;
    if (!empty($_GET['edit'])) { $stmt = db()->prepare('SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?'); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); }
    $products = db()->query('SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.is_active DESC, c.sort_order, c.name, p.sort_order, p.name')->fetchAll();
    render_header('პროდუქტები');
    echo '<div class="page-head"><h1>პროდუქტები</h1></div><section class="two-col"><div class="card"><h2>'.($edit?'რედაქტირება':'ახალი პროდუქტი').'</h2><form class="stack" method="post"><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="'.h($edit['id'] ?? '').'"><label>სახელი<input name="name" required value="'.h($edit['name'] ?? '').'"></label><label>ფასი<input name="price" type="number" step="0.01" min="0" required value="'.h($edit['price'] ?? '').'"></label><label>კატეგორია<input name="category_name" value="'.h($edit['category_name'] ?? 'სხვა').'"></label><label class="check"><input type="checkbox" name="is_active" '.(!$edit || (int)$edit['is_active']===1?'checked':'').'> აქტიური</label><button class="btn success">შენახვა</button></form></div><div class="card"><h2>პროდუქტების სია</h2><div class="table-wrap"><table><thead><tr><th>პროდუქტი</th><th>კატეგორია</th><th>ფასი</th><th>სტატუსი</th><th>ქმედება</th></tr></thead><tbody>';
    foreach ($products as $p) {
        echo '<tr><td>'.h($p['name']).'</td><td>'.h($p['category_name']).'</td><td>'.money($p['price']).'</td><td>'.((int)$p['is_active']?'აქტიური':'გათიშული').'</td><td><a href="'.h(url_for('products',['edit'=>(int)$p['id']])).'">რედაქტირება</a><form method="post" style="margin-top:8px"><input type="hidden" name="action" value="toggle_product"><input type="hidden" name="id" value="'.(int)$p['id'].'"><input type="hidden" name="is_active" value="'.((int)$p['is_active']?0:1).'"><button class="btn '.((int)$p['is_active']?'danger':'success').'">'.((int)$p['is_active']?'გათიშვა':'ჩართვა').'</button></form></td></tr>';
    }
    echo '</tbody></table></div></div></section>';
    render_footer();
}

function page_reports(): void {
    require_admin();
    $days = db()->query('SELECT * FROM business_days ORDER BY id DESC LIMIT 60')->fetchAll();
    $dayId = (int)($_GET['day_id'] ?? ($days[0]['id'] ?? 0));
    $day = $dayId ? fetch_day($dayId) : null;
    render_header('რეპორტები');
    echo '<div class="page-head"><h1>რეპორტები</h1></div><section class="two-col reports"><div class="card"><h2>დღეები</h2><div class="day-list">';
    if (!$days) echo '<p class="muted">დღეები ჯერ არ არის.</p>';
    foreach ($days as $d) { echo '<a class="day-link '.((int)$d['id']===$dayId?'active':'').'" href="'.h(url_for('reports',['day_id'=>(int)$d['id']])).'"><strong>#'.(int)$d['id'].' — '.h($d['status']==='open'?'ღია':'დახურული').'</strong><small>'.h($d['opened_at']).'</small></a>'; }
    echo '</div></div><div class="stack">';
    if (!$day) { echo '<section class="card"><p class="muted">რეპორტი არ არის.</p></section></div></section>'; render_footer(); return; }
    $summary = day_summary((int)$day['id']);
    echo '<section class="stats"><div><span>გაყიდვები</span><strong>'.money($summary['sales_total']).'</strong></div><div><span>ნაღდი</span><strong>'.money($summary['cash_total']).'</strong></div><div><span>ბარათი</span><strong>'.money($summary['card_total']).'</strong></div><div><span>დახურული მაგიდები</span><strong>'.$summary['orders_count'].'</strong></div><div><span>გაუქმებები</span><strong>'.$summary['cancelled_count'].'</strong></div></section>';
    echo '<section class="card"><h2>დღის დეტალები</h2><p>გახსნა: '.h($day['opened_at']).'<br>დახურვა: '.h($day['closed_at'] ?: '—').'<br>საწყისი ნაღდი: '.money($day['opening_cash']).'<br>მოსალოდნელი ნაღდი: '.money($day['expected_cash'] ?? ((float)$day['opening_cash']+(float)$summary['cash_total'])).'<br>დათვლილი ნაღდი: '.money($day['closing_cash'] ?? 0).'<br>სხვაობა: '.money($day['cash_difference'] ?? 0).'</p></section>';
    $stmt = db()->prepare('SELECT product_name, SUM(quantity) qty, SUM(quantity*price) total FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.business_day_id=? AND o.status="closed" AND oi.is_cancelled=0 GROUP BY product_name ORDER BY qty DESC'); $stmt->execute([$day['id']]); $top = $stmt->fetchAll();
    echo '<section class="card"><h2>პროდუქტების გაყიდვები</h2><div class="table-wrap"><table><thead><tr><th>პროდუქტი</th><th>რაოდ.</th><th>ჯამი</th></tr></thead><tbody>'; foreach ($top as $p) echo '<tr><td>'.h($p['product_name']).'</td><td>'.h(qty($p['qty'])).'</td><td>'.money($p['total']).'</td></tr>'; echo '</tbody></table></div></section>';
    $stmt = db()->prepare('SELECT o.*, t.name table_name, u.name user_name FROM orders o JOIN restaurant_tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.user_id WHERE o.business_day_id=? AND o.status="closed" ORDER BY o.closed_at DESC'); $stmt->execute([$day['id']]); $orders = $stmt->fetchAll();
    echo '<section class="card"><h2>დახურული ანგარიშები</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>მაგიდა</th><th>მომხმარებელი</th><th>ჯამი</th><th>გადახდა</th><th>დრო</th></tr></thead><tbody>'; foreach ($orders as $o) echo '<tr><td>#'.(int)$o['id'].'</td><td>'.h($o['table_name']).'</td><td>'.h($o['user_name']).'</td><td>'.money($o['total']).'</td><td>'.h(payment_label($o['payment_type'])).'</td><td>'.h($o['closed_at']).'</td></tr>'; echo '</tbody></table></div></section>';
    $stmt = db()->prepare('SELECT oi.*, t.name table_name, u.name cancel_user FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN restaurant_tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=oi.cancelled_by WHERE o.business_day_id=? AND oi.is_cancelled=1 ORDER BY oi.cancelled_at DESC'); $stmt->execute([$day['id']]); $cancelled = $stmt->fetchAll();
    echo '<section class="card"><h2>გაუქმებული პროდუქტები</h2><div class="table-wrap"><table><thead><tr><th>მაგიდა</th><th>პროდუქტი</th><th>რაოდ.</th><th>მიზეზი</th><th>ვინ</th><th>დრო</th></tr></thead><tbody>'; foreach ($cancelled as $c) echo '<tr><td>'.h($c['table_name']).'</td><td>'.h($c['product_name']).'</td><td>'.h(qty($c['quantity'])).'</td><td>'.h($c['cancel_reason']).'</td><td>'.h($c['cancel_user']).'</td><td>'.h($c['cancelled_at']).'</td></tr>'; echo '</tbody></table></div></section>';
    echo '</div></section>';
    render_footer();
}

function page_history(): void {
    require_admin();
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    $from = $_GET['from'] ?? $monthStart;
    $to = $_GET['to'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $monthStart;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = $today;
    $tableId = (int)($_GET['table_id'] ?? 0);
    $payment = $_GET['payment'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $viewOrderId = (int)($_GET['order_id'] ?? 0);
    $sel = fn($a, $b) => (string)$a === (string)$b ? 'selected' : '';
    $tables = db()->query('SELECT * FROM restaurant_tables WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();

    $where = ['o.status IN ("closed", "cancelled")', 'COALESCE(o.closed_at, o.created_at) BETWEEN ? AND ?'];
    $params = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($tableId > 0) { $where[] = 'o.table_id=?'; $params[] = $tableId; }
    if (in_array($payment, ['cash','card','mixed'], true)) { $where[] = 'o.payment_type=?'; $params[] = $payment; }
    if ($payment === 'zero') { $where[] = 'o.status="cancelled"'; }
    if (in_array($status, ['closed','cancelled'], true)) { $where[] = 'o.status=?'; $params[] = $status; }

    $sql = 'SELECT o.*, t.name table_name, u.name user_name, d.id day_id FROM orders o JOIN restaurant_tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.user_id LEFT JOIN business_days d ON d.id=o.business_day_id WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(o.closed_at,o.created_at) DESC, o.id DESC LIMIT 300';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $salesTotal = 0; $cashTotal = 0; $cardTotal = 0; $closedCount = 0; $zeroCount = 0;
    foreach ($orders as $o) {
        if ($o['status'] === 'closed') {
            $closedCount++;
            $salesTotal += (float)$o['total'];
            $cashTotal += (float)$o['cash_amount'];
            $cardTotal += (float)$o['card_amount'];
        } elseif ($o['status'] === 'cancelled') {
            $zeroCount++;
        }
    }

    render_header('ისტორია');
    echo '<style>.history-filters{margin-bottom:18px}.history-grid{display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:12px;align-items:end}.history-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.status-zero{display:inline-flex;border-radius:999px;background:#ffe5e2;color:#8b1d15;font-weight:900;padding:5px 9px}.status-paid{display:inline-flex;border-radius:999px;background:#e9ffe4;color:#24733c;font-weight:900;padding:5px 9px}.history-detail{margin-bottom:18px}.history-detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px}.history-detail-grid div{background:#fff;border:1px solid var(--line);border-radius:14px;padding:10px}.history-detail-grid span{display:block;color:var(--muted);font-size:.86rem}.history-detail-grid strong{display:block;margin-top:3px}@media(max-width:980px){.history-grid{grid-template-columns:1fr 1fr}}@media(max-width:560px){.history-grid{grid-template-columns:1fr}}</style>';
    echo '<div class="page-head"><div><h1>მაგიდების ისტორია</h1><p class="muted">მხოლოდ ადმინისტრატორისთვის — დახურული და ნულით დახურული მაგიდები.</p></div></div>';

    echo '<section class="card history-filters"><form method="get"><input type="hidden" name="page" value="history"><div class="history-grid"><label>თარიღიდან<input type="date" name="from" value="'.h($from).'"></label><label>თარიღამდე<input type="date" name="to" value="'.h($to).'"></label><label>მაგიდა<select name="table_id"><option value="0">ყველა მაგიდა</option>';
    foreach ($tables as $t) echo '<option value="'.(int)$t['id'].'" '.$sel($tableId, $t['id']).'>'.h($t['name']).'</option>';
    echo '</select></label><label>გადახდა<select name="payment"><option value="all" '.$sel($payment,'all').'>ყველა</option><option value="cash" '.$sel($payment,'cash').'>ნაღდი</option><option value="card" '.$sel($payment,'card').'>ბარათი</option><option value="mixed" '.$sel($payment,'mixed').'>შერეული</option><option value="zero" '.$sel($payment,'zero').'>ნულით დახურული</option></select></label><label>სტატუსი<select name="status"><option value="all" '.$sel($status,'all').'>ყველა</option><option value="closed" '.$sel($status,'closed').'>დახურული</option><option value="cancelled" '.$sel($status,'cancelled').'>ნულით დახურული</option></select></label><button class="btn primary">ძებნა</button></div></form><div class="history-actions"><a class="btn" href="'.h(url_for('history',['from'=>$today,'to'=>$today])).'">დღეს</a><a class="btn" href="'.h(url_for('history',['from'=>date('Y-m-d', strtotime('-1 day')),'to'=>date('Y-m-d', strtotime('-1 day'))])).'">გუშინ</a><a class="btn" href="'.h(url_for('history',['from'=>$monthStart,'to'=>$today])).'">ამ თვეში</a></div></section>';

    echo '<section class="stats"><div><span>სულ გაყიდვა</span><strong>'.money($salesTotal).'</strong></div><div><span>ნაღდი</span><strong>'.money($cashTotal).'</strong></div><div><span>ბარათი</span><strong>'.money($cardTotal).'</strong></div><div><span>დახურული ანგარიშები</span><strong>'.$closedCount.'</strong></div><div><span>ნულით დახურული</span><strong>'.$zeroCount.'</strong></div></section>';

    if ($viewOrderId > 0) {
        $stmt = db()->prepare('SELECT o.*, t.name table_name, u.name user_name FROM orders o JOIN restaurant_tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.user_id WHERE o.id=? AND o.status IN ("closed","cancelled")');
        $stmt->execute([$viewOrderId]);
        $detail = $stmt->fetch();
        if ($detail) {
            $items = order_items((int)$detail['id']);
            echo '<section class="card history-detail"><div class="page-head"><h2>ანგარიში #'.(int)$detail['id'].'</h2><a class="btn" href="'.h(url_for('history',['from'=>$from,'to'=>$to,'table_id'=>$tableId,'payment'=>$payment,'status'=>$status])).'">დახურვა</a></div><div class="history-detail-grid"><div><span>მაგიდა</span><strong>'.h($detail['table_name']).'</strong></div><div><span>სტატუსი</span><strong>'.h($detail['status']==='cancelled'?'ნულით დახურული':'დახურული').'</strong></div><div><span>მომხმარებელი</span><strong>'.h($detail['user_name'] ?: '—').'</strong></div><div><span>გადახდა</span><strong>'.h($detail['status']==='cancelled'?'—':payment_label($detail['payment_type'])).'</strong></div><div><span>ჯამი</span><strong>'.money($detail['total']).'</strong></div><div><span>დრო</span><strong>'.h($detail['closed_at'] ?: $detail['created_at']).'</strong></div></div><h3>პროდუქტები</h3><div class="table-wrap"><table><thead><tr><th>პროდუქტი</th><th>რაოდ.</th><th>ფასი</th><th>ჯამი</th><th>სტატუსი / მიზეზი</th></tr></thead><tbody>';
            foreach ($items as $it) {
                $lineTotal = (float)$it['quantity'] * (float)$it['price'];
                $itemStatus = (int)$it['is_cancelled'] === 1 ? 'გაუქმებულია: '.($it['cancel_reason'] ?: '—') : 'გაყიდულია';
                echo '<tr><td>'.h($it['product_name']).'</td><td>'.h(qty($it['quantity'])).'</td><td>'.money($it['price']).'</td><td>'.money($lineTotal).'</td><td>'.h($itemStatus).'</td></tr>';
            }
            echo '</tbody></table></div></section>';
        }
    }

    echo '<section class="card"><h2>ანგარიშების სია</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>დღე</th><th>მაგიდა</th><th>მომხმარებელი</th><th>ჯამი</th><th>გადახდა</th><th>სტატუსი</th><th>დრო</th><th>ნახვა</th></tr></thead><tbody>';
    if (!$orders) echo '<tr><td colspan="9">ამ ფილტრით ისტორია არ მოიძებნა.</td></tr>';
    foreach ($orders as $o) {
        $isZero = $o['status'] === 'cancelled';
        $statusHtml = $isZero ? '<span class="status-zero">ნულით</span>' : '<span class="status-paid">დახურული</span>';
        $payText = $isZero ? '—' : payment_label($o['payment_type']);
        echo '<tr><td>#'.(int)$o['id'].'</td><td>#'.(int)$o['day_id'].'</td><td>'.h($o['table_name']).'</td><td>'.h($o['user_name'] ?: '—').'</td><td>'.money($o['total']).'</td><td>'.h($payText).'</td><td>'.$statusHtml.'</td><td>'.h($o['closed_at'] ?: $o['created_at']).'</td><td><a class="btn" href="'.h(url_for('history',['from'=>$from,'to'=>$to,'table_id'=>$tableId,'payment'=>$payment,'status'=>$status,'order_id'=>(int)$o['id']])).'">ნახვა</a></td></tr>';
    }
    echo '</tbody></table></div></section>';
    render_footer();
}

try {
    if (!file_exists(__DIR__ . '/config.php')) { setup_page(); exit; }
    handle_post_action();
    $page = $_GET['page'] ?? 'day';
    if ($page === 'logout') { session_destroy(); redirect_to('login'); }
    match ($page) {
        'login' => page_login(),
        'day' => page_day(),
        'tables' => page_tables(),
        'table' => page_table(),
        'products' => page_products(),
        'history' => page_history(),
        'print_order' => page_print_order(),
        'print_final' => page_print_final(),
        'reports' => page_reports(),
        default => redirect_to('day'),
    };
} catch (Throwable $e) {
    render_header('შეცდომა');
    echo '<section class="card narrow error"><h1>შეცდომა</h1><p>შეამოწმე config.php და MySQL ბაზის import.</p><pre>'.h($e->getMessage()).'</pre></section>';
    render_footer();
}
