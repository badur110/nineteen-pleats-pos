<?php
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
$wanted = [
    ['name' => 'მაგიდა 11', 'sort_order' => 11],
    ['name' => 'მაგიდა 12', 'sort_order' => 12],
];

try {
    $before = $pdo->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active=1 AND name IN ('მაგიდა 11','მაგიდა 12')")->fetchColumn();

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO restaurant_tables (name, sort_order, is_active) VALUES (?, ?, 1) '
        . 'ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order), is_active=1'
    );
    foreach ($wanted as $table) {
        $stmt->execute([$table['name'], $table['sort_order']]);
    }
    $pdo->commit();

    $after = $pdo->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active=1 AND name IN ('მაგიდა 11','მაგიდა 12')")->fetchColumn();

    echo json_encode([
        'ok' => true,
        'changed' => (int)$before < 2 && (int)$after === 2,
        'active_tables_added' => max(0, (int)$after - (int)$before),
        'total_target' => 12,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Tables could not be synchronized'], JSON_UNESCAPED_UNICODE);
}
