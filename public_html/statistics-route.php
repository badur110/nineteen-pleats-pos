<?php
require_once __DIR__ . '/includes/business-day.php';

$sourceFile = __DIR__ . '/statistics.php';
$source = file_get_contents($sourceFile);
if ($source === false) {
    http_response_code(500);
    exit('Statistics source could not be loaded.');
}

$replace = static function (string $old, string $new, string $label) use (&$source): void {
    $count = 0;
    $source = str_replace($old, $new, $source, $count);
    if ($count === 0) {
        error_log('GARBALIA 4AM statistics patch missed: ' . $label);
    }
};

$oldDateRange = <<<'PHP'
function stat_date_range(string $range): array {
    $today = date('Y-m-d');
    switch ($range) {
        case 'today':
            return [$today, $today, 'დღეს'];
        case 'yesterday':
            $day = date('Y-m-d', strtotime('-1 day'));
            return [$day, $day, 'გუშინ'];
        case 'last7':
            return [date('Y-m-d', strtotime('-6 day')), $today, 'ბოლო 7 დღე'];
        case 'prev_month':
            return [date('Y-m-01', strtotime('first day of previous month')), date('Y-m-t', strtotime('last day of previous month')), 'წინა თვე'];
        case 'year':
            return [date('Y-01-01'), $today, 'ამ წელს'];
        default:
            return [date('Y-m-01'), $today, 'ამ თვეში'];
    }
}
PHP;

$newDateRange = <<<'PHP'
function stat_date_range(string $range): array {
    $today = garbalia_business_date();
    $base = new DateTimeImmutable($today . ' 12:00:00');
    switch ($range) {
        case 'today':
            return [$today, $today, 'დღეს'];
        case 'yesterday':
            $day = $base->modify('-1 day')->format('Y-m-d');
            return [$day, $day, 'გუშინ'];
        case 'last7':
            return [$base->modify('-6 days')->format('Y-m-d'), $today, 'ბოლო 7 დღე'];
        case 'prev_month':
            $previous = $base->modify('first day of previous month');
            return [$previous->format('Y-m-01'), $previous->format('Y-m-t'), 'წინა თვე'];
        case 'year':
            return [$base->format('Y-01-01'), $today, 'ამ წელს'];
        default:
            return [$base->format('Y-m-01'), $today, 'ამ თვეში'];
    }
}
PHP;
$replace($oldDateRange, $newDateRange, 'stat_date_range');

$oldSalesBetween = <<<'PHP'
function sales_between(string $from, string $to): array {
    $stmt = db()->prepare("SELECT COUNT(*) orders_count, COALESCE(SUM(total),0) total FROM orders WHERE status='closed' AND COALESCE(closed_at,created_at) BETWEEN ? AND ?");
    $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
    $row = $stmt->fetch() ?: [];
    return [
        'orders_count' => (int)($row['orders_count'] ?? 0),
        'total' => (float)($row['total'] ?? 0),
    ];
}
PHP;

$newSalesBetween = <<<'PHP'
function sales_between(string $from, string $to): array {
    [$startDateTime, $endDateTime] = garbalia_business_range($from, $to);
    $stmt = db()->prepare("SELECT COUNT(*) orders_count, COALESCE(SUM(total),0) total FROM orders WHERE status='closed' AND COALESCE(closed_at,created_at) BETWEEN ? AND ?");
    $stmt->execute([$startDateTime, $endDateTime]);
    $row = $stmt->fetch() ?: [];
    return [
        'orders_count' => (int)($row['orders_count'] ?? 0),
        'total' => (float)($row['total'] ?? 0),
    ];
}
PHP;
$replace($oldSalesBetween, $newSalesBetween, 'sales_between');

$replace(
    "$startDateTime = $from . ' 00:00:00';\n$endDateTime = $to . ' 23:59:59';",
    "[$startDateTime, $endDateTime] = garbalia_business_range($from, $to);",
    'main datetime range'
);

$oldQuickSales = <<<'PHP'
$todaySales = sales_between(date('Y-m-d'), date('Y-m-d'));
$yesterdaySales = sales_between(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day')));
$monthSales = sales_between(date('Y-m-01'), date('Y-m-d'));
$prevMonthSales = sales_between(date('Y-m-01', strtotime('first day of previous month')), date('Y-m-t', strtotime('last day of previous month')));
PHP;

$newQuickSales = <<<'PHP'
$businessToday = garbalia_business_date();
$businessYesterday = garbalia_business_date_shift(-1);
$businessMonthStart = garbalia_business_month_start($businessToday);
$businessBase = new DateTimeImmutable($businessToday . ' 12:00:00');
$businessPreviousMonth = $businessBase->modify('first day of previous month');
$todaySales = sales_between($businessToday, $businessToday);
$yesterdaySales = sales_between($businessYesterday, $businessYesterday);
$monthSales = sales_between($businessMonthStart, $businessToday);
$prevMonthSales = sales_between($businessPreviousMonth->format('Y-m-01'), $businessPreviousMonth->format('Y-m-t'));
PHP;
$replace($oldQuickSales, $newQuickSales, 'quick comparison sales');

ob_start();
eval('?>' . $source);
$html = ob_get_clean();

$reloadAfterMs = max(1000, (garbalia_next_business_cutoff_timestamp() - time() + 2) * 1000);
$cutoffHour = garbalia_business_cutoff_hour();
$cutoffLabel = sprintf('%02d:00–%02d:59', $cutoffHour, ($cutoffHour + 23) % 24);
$runtimeScript = '<script>(function(){'
    . 'window.setTimeout(function(){window.location.reload();},' . (int)$reloadAfterMs . ');'
    . 'var sub=document.querySelector(".statistics-sub");'
    . 'if(sub&&!sub.querySelector("[data-business-cutoff]")){'
    . 'var note=document.createElement("span");'
    . 'note.setAttribute("data-business-cutoff","1");'
    . 'note.style.cssText="display:inline-flex;margin-left:8px;padding:4px 8px;border-radius:999px;background:#fff1d8;color:#79501e;font-size:.72rem;font-weight:900;vertical-align:middle";'
    . 'note.textContent="ოპერაციული დღე 04:00–03:59";sub.appendChild(note);}'
    . '})();</script>';

if (strpos($html, '</body>') !== false) {
    echo str_replace('</body>', $runtimeScript . '</body>', $html);
} else {
    echo $html . $runtimeScript;
}
