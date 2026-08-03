<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/business-day.php';

function garbalia_patch_history_source(string $source, bool $admin): string {
    $replace = static function (string $old, string $new, string $label) use (&$source): void {
        $count = 0;
        $source = str_replace($old, $new, $source, $count);
        if ($count === 0) {
            error_log('GARBALIA 4AM history patch missed: ' . $label);
        }
    };

    if ($admin) {
        $oldDates = <<<'PHP'
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
PHP;
        $newDates = <<<'PHP'
    $today = garbalia_business_date();
    $monthStart = garbalia_business_month_start($today);
PHP;
        $replace($oldDates, $newDates, 'admin logical dates');

        $oldParams = <<<'PHP'
    $params = [$from . ' 00:00:00', $to . ' 23:59:59'];
PHP;
        $newParams = <<<'PHP'
    [$historyStartDateTime, $historyEndDateTime] = garbalia_business_range($from, $to);
    $params = [$historyStartDateTime, $historyEndDateTime];
PHP;
        $replace($oldParams, $newParams, 'admin SQL date range');

        $source = str_replace(
            "date('Y-m-d', strtotime('-1 day'))",
            'garbalia_business_date_shift(-1)',
            $source
        );
        $source = str_replace(
            'მხოლოდ ადმინისტრატორისთვის — დახურული მაგიდები, პროდუქტის ძებნა, მომხმარებელი და Excel ჩამოტვირთვა.',
            'მხოლოდ ადმინისტრატორისთვის — ოპერაციული დღე ითვლება 04:00-დან მომდევნო დღის 03:59-მდე.',
            $source
        );
        return $source;
    }

    $oldCashierDates = <<<'PHP'
$today = date('Y-m-d');
$limitFrom = date('Y-m-d', strtotime('-6 days'));
PHP;
    $newCashierDates = <<<'PHP'
$today = garbalia_business_date();
$limitFrom = garbalia_business_date_shift(-6);
PHP;
    $replace($oldCashierDates, $newCashierDates, 'cashier logical dates');

    $oldCashierParams = <<<'PHP'
$params = [$from . ' 00:00:00', $to . ' 23:59:59'];
PHP;
    $newCashierParams = <<<'PHP'
[$historyStartDateTime, $historyEndDateTime] = garbalia_business_range($from, $to);
$params = [$historyStartDateTime, $historyEndDateTime];
PHP;
    $replace($oldCashierParams, $newCashierParams, 'cashier SQL date range');

    $oldDetailRange = <<<'PHP'
    $stmt->execute([$viewOrderId, $limitFrom . ' 00:00:00', $today . ' 23:59:59']);
PHP;
    $newDetailRange = <<<'PHP'
    [$detailStartDateTime, $detailEndDateTime] = garbalia_business_range($limitFrom, $today);
    $stmt->execute([$viewOrderId, $detailStartDateTime, $detailEndDateTime]);
PHP;
    $replace($oldDetailRange, $newDetailRange, 'cashier detail range');

    $source = str_replace(
        "date('Y-m-d', strtotime('-1 day'))",
        'garbalia_business_date_shift(-1)',
        $source
    );
    $source = str_replace(
        'მოლარის წვდომა — ბოლო 7 დღის ანგარიშების ნახვა და ქვითრის ხელახლა დაბეჭდვა.',
        'მოლარის წვდომა — დღე ითვლება 04:00-დან მომდევნო დღის 03:59-მდე.',
        $source
    );

    return $source;
}

if (($_SESSION['user']['role'] ?? '') === 'admin') {
    $_GET['page'] = 'history';
    $source = file_get_contents(__DIR__ . '/index.php');
    if ($source === false) {
        http_response_code(500);
        exit('History source could not be loaded.');
    }
    $source = garbalia_patch_history_source($source, true);

    ob_start();
    eval('?>' . $source);
    $html = ob_get_clean();

    $fixScript = <<<'HTML'
<script>
(function () {
  function fixHistoryFilterSubmit() {
    const form = document.querySelector('.history-filters form');
    if (!form) return;

    if (!form.id) form.id = 'garbalia-history-filter-form';

    const candidates = Array.from(document.querySelectorAll(
      '.history-clean-actions button, .history-actions button, .history-filters button'
    ));
    const searchButton = candidates.find(function (button) {
      return button.textContent.trim() === 'ძებნა' || button.classList.contains('primary');
    });

    if (!searchButton) return;

    searchButton.type = 'submit';
    searchButton.setAttribute('form', form.id);

    if (searchButton.dataset.garbaliaHistorySubmitFixed === '1') return;
    searchButton.dataset.garbaliaHistorySubmitFixed = '1';

    searchButton.addEventListener('click', function (event) {
      if (searchButton.closest('form') === form) return;
      event.preventDefault();
      if (typeof form.requestSubmit === 'function') form.requestSubmit();
      else form.submit();
    });
  }

  function runFixes() {
    window.setTimeout(fixHistoryFilterSubmit, 0);
    window.setTimeout(fixHistoryFilterSubmit, 150);
    window.setTimeout(fixHistoryFilterSubmit, 500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runFixes);
  } else {
    runFixes();
  }
})();
</script>
HTML;

    if (strpos($html, '</body>') !== false) {
        echo str_replace('</body>', $fixScript . '</body>', $html);
    } else {
        echo $html . $fixScript;
    }
    exit;
}

$source = file_get_contents(__DIR__ . '/history.php');
if ($source === false) {
    http_response_code(500);
    exit('History source could not be loaded.');
}
$source = garbalia_patch_history_source($source, false);
eval('?>' . $source);
