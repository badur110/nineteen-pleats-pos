<?php

function ensure_order_numbering(): void {
    static $done = false;
    if ($done) return;

    $pdo = db();
    $lockName = 'garbalia_order_numbering_setup';
    $locked = false;

    try {
        $stmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
        $stmt->execute([$lockName]);
        $locked = (int)$stmt->fetchColumn() === 1;

        if (!table_has_column('orders', 'receipt_number')) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN receipt_number INT UNSIGNED NULL AFTER id');
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS order_number_sequence (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $hasUnique = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND INDEX_NAME='uniq_orders_receipt_number'")->fetchColumn();
        if ((int)$hasUnique === 0) {
            $pdo->exec('ALTER TABLE orders ADD UNIQUE KEY uniq_orders_receipt_number (receipt_number)');
        }

        $maxReceipt = (int)$pdo->query('SELECT COALESCE(MAX(receipt_number), 999) FROM orders')->fetchColumn();
        $maxSequence = (int)$pdo->query('SELECT COALESCE(MAX(id), 999) FROM order_number_sequence')->fetchColumn();
        $next = max(1000, $maxReceipt + 1, $maxSequence + 1);

        $missing = $pdo->query('SELECT id FROM orders WHERE receipt_number IS NULL OR receipt_number=0 ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
        if ($missing) {
            $pdo->beginTransaction();
            $update = $pdo->prepare('UPDATE orders SET receipt_number=? WHERE id=? AND (receipt_number IS NULL OR receipt_number=0)');
            foreach ($missing as $orderId) {
                $update->execute([$next, (int)$orderId]);
                if ($update->rowCount() > 0) $next++;
            }
            $pdo->commit();
        }

        $maxReceipt = (int)$pdo->query('SELECT COALESCE(MAX(receipt_number), 999) FROM orders')->fetchColumn();
        $maxSequence = (int)$pdo->query('SELECT COALESCE(MAX(id), 999) FROM order_number_sequence')->fetchColumn();
        $next = max(1000, $maxReceipt + 1, $maxSequence + 1);
        $pdo->exec('ALTER TABLE order_number_sequence AUTO_INCREMENT=' . $next);

        $done = true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } finally {
        if ($locked) {
            try {
                $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $stmt->execute([$lockName]);
            } catch (Throwable $ignored) {
            }
        }
    }
}

function ensure_order_receipt_number(int $orderId): int {
    ensure_order_numbering();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT receipt_number FROM orders WHERE id=? LIMIT 1');
    $stmt->execute([$orderId]);
    $number = (int)$stmt->fetchColumn();
    if ($number > 0) return $number;

    $lockName = 'garbalia_order_number_' . $orderId;
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 10)');
    $lock->execute([$lockName]);
    $locked = (int)$lock->fetchColumn() === 1;

    try {
        $stmt->execute([$orderId]);
        $number = (int)$stmt->fetchColumn();
        if ($number > 0) return $number;

        $pdo->beginTransaction();
        $pdo->exec('INSERT INTO order_number_sequence () VALUES ()');
        $number = (int)$pdo->lastInsertId();
        $update = $pdo->prepare('UPDATE orders SET receipt_number=? WHERE id=? AND (receipt_number IS NULL OR receipt_number=0)');
        $update->execute([$number, $orderId]);
        $pdo->commit();

        if ($update->rowCount() === 0) {
            $stmt->execute([$orderId]);
            $number = (int)$stmt->fetchColumn();
        }
        return $number;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } finally {
        if ($locked) {
            try {
                $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$lockName]);
            } catch (Throwable $ignored) {
            }
        }
    }
}

function receipt_number_for_order($order): int {
    if (is_array($order)) {
        $number = (int)($order['receipt_number'] ?? 0);
        if ($number > 0) return $number;
        $orderId = (int)($order['id'] ?? 0);
    } else {
        $orderId = (int)$order;
    }
    return $orderId > 0 ? ensure_order_receipt_number($orderId) : 0;
}

function add_receipt_number_to_text(string $text, int $receiptNumber): string {
    if ($receiptNumber <= 0) return $text;
    $line = 'ქვითრის ნომერი: #' . $receiptNumber;
    if (strpos($text, $line) !== false) return $text;

    $updated = preg_replace('/^-{8,}\R/m', $line . "\n$0", $text, 1);
    return is_string($updated) && $updated !== $text ? $updated : ($line . "\n" . $text);
}
