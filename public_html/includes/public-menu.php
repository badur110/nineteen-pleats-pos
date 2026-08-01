<?php

function public_menu_initial(string $name): string {
    $name = trim($name);
    if ($name === '') return '•';
    return function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
}

function public_menu_tone(string $name): int {
    return hexdec(substr(md5($name), 0, 2)) % 6;
}

function public_menu_payload(): array {
    $sql = "SELECT
                p.id,
                p.name,
                p.price,
                p.created_at,
                p.updated_at,
                c.id AS category_id,
                COALESCE(c.name, 'სხვა') AS category_name,
                COALESCE(c.sort_order, 999) AS category_sort
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1
              AND (c.id IS NULL OR c.is_active = 1)
            ORDER BY
                CASE
                    WHEN c.name LIKE '%ხინკ%' THEN 0
                    WHEN c.name LIKE '%სასმ%' THEN 1
                    ELSE 2
                END,
                category_sort,
                category_name,
                p.sort_order,
                p.name";

    $rows = db()->query($sql)->fetchAll();
    $groups = [];
    $lastUpdated = '';

    foreach ($rows as $row) {
        $categoryName = trim((string)($row['category_name'] ?? 'სხვა')) ?: 'სხვა';
        $categoryId = !empty($row['category_id']) ? 'category-' . (int)$row['category_id'] : 'category-other';

        if (!isset($groups[$categoryId])) {
            $groups[$categoryId] = [
                'id' => $categoryId,
                'name' => $categoryName,
                'initial' => public_menu_initial($categoryName),
                'tone' => public_menu_tone($categoryName),
                'products' => [],
            ];
        }

        $groups[$categoryId]['products'][] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'price' => round((float)$row['price'], 2),
        ];

        $candidate = (string)($row['updated_at'] ?: $row['created_at'] ?: '');
        if ($candidate > $lastUpdated) $lastUpdated = $candidate;
    }

    $categories = array_values($groups);
    foreach ($categories as &$category) {
        $category['count'] = count($category['products']);
    }
    unset($category);

    $versionSource = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return [
        'restaurant' => [
            'name' => (string)cfg('restaurant_name', 'ცხრამეტი ნაოჭი'),
            'name_en' => (string)cfg('restaurant_name_en', 'Nineteen Pleats'),
            'phone' => (string)cfg('restaurant_phone', ''),
            'address' => (string)cfg('restaurant_address', ''),
        ],
        'categories' => $categories,
        'product_count' => array_sum(array_map(static fn(array $category): int => (int)$category['count'], $categories)),
        'updated_at' => $lastUpdated,
        'version' => substr(hash('sha256', $versionSource ?: 'empty-menu'), 0, 20),
    ];
}
