<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/order-numbers.php';
require __DIR__ . '/includes/receipt-templates.php';

require_login();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function json_fail(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['ok'=>false,'message'=>$message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_fail('არასწორი მოთხოვნა.', 405);

$day = active_day();
if (!$day) json_fail('სამუშაო დღე დახურულია.');

$tableId = (int)($_POST['table_id'] ?? 0);
$table = fetch_table($tableId);
if (!$table) json_fail('მაგიდა ვერ მოიძებნა.');

$order = current_open_order((int)$day['id'], $tableId);
if (!$order) json_fail('ამ მაგიდაზე შეკვეთა არ არის.');

$stmt = db()->prepare('SELECT * FROM order_items WHERE order_id=? AND is_cancelled=0 AND sent_at IS NULL ORDER BY id ASC');
$stmt->execute([(int)$order['id']]);
$items = $stmt->fetchAll();
if (!$items) json_fail('ახალი გასაგზავნი პროდუქტი არ არის.');

$ids = array_map(function ($item) { return (int)$item['id']; }, $items);
$pdo = db();
try {
    $pdo->beginTransaction();
    $sql = 'UPDATE order_items SET sent_at=NOW() WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') AND sent_at IS NULL';
    $pdo->prepare($sql)->execute($ids);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_fail('შეკვეთის გაგზავნა ვერ მოხერხდა.', 500);
}

$receiptNumber = receipt_number_for_order($order);
$barTemplate = receipt_template('bar');
$kitchenTemplate = receipt_template('kitchen');

$result = [
    'ok' => true,
    'message' => 'შეკვეთა გაიგზავნა და მზადაა დასაბეჭდად.',
    'table_id' => $tableId,
    'order_id' => (int)$order['id'],
    'receipt_number' => $receiptNumber,
    'bar' => [
        'text' => build_configurable_bar_receipt($table, $items, $receiptNumber),
        'font_size' => (int)$barTemplate['font_size'],
    ],
    'kitchen' => [
        'text' => build_configurable_kitchen_receipt($table, $items, $receiptNumber),
        'font_size' => (int)$kitchenTemplate['font_size'],
    ],
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
