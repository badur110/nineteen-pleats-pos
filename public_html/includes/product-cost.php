<?php

function ensure_product_cost_schema(): void {
    static $done = false;
    if ($done) return;

    $pdo = db();

    if (!table_has_column('products', 'cost')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price");
    }

    if (!table_has_column('order_items', 'product_cost')) {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN product_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?");
    $stmt->execute(['trg_order_items_product_cost']);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("CREATE TRIGGER trg_order_items_product_cost
            BEFORE INSERT ON order_items
            FOR EACH ROW
            SET NEW.product_cost = COALESCE((SELECT cost FROM products WHERE id=NEW.product_id LIMIT 1), 0.00)");
    }

    $done = true;
}

function product_unit_profit(array $product): float {
    return round((float)($product['price'] ?? 0) - (float)($product['cost'] ?? 0), 2);
}

function product_margin_percent(array $product): float {
    $price = (float)($product['price'] ?? 0);
    if ($price <= 0) return 0.0;
    return round(product_unit_profit($product) / $price * 100, 1);
}
