<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SESSION['user']['role'] ?? '') === 'admin') {
    $_GET['page'] = 'history';

    ob_start();
    require __DIR__ . '/index.php';
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

  function addHistoryResetButton() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('order_id') || document.querySelector('[data-history-reset-link]')) return;

    const link = document.createElement('a');
    link.href = '/reset-history';
    link.className = 'btn danger';
    link.textContent = 'ისტორიის განულება';
    link.setAttribute('data-history-reset-link', '1');
    link.title = 'ყველა ოპერაციული ისტორიის უსაფრთხოდ განულება';

    const actions = document.querySelector('.history-clean-actions, .history-actions');
    if (actions) {
      actions.appendChild(link);
      return;
    }

    const head = document.querySelector('.wrap > .page-head');
    if (head) head.appendChild(link);
  }

  function runFixes() {
    window.setTimeout(function () { fixHistoryFilterSubmit(); addHistoryResetButton(); }, 0);
    window.setTimeout(function () { fixHistoryFilterSubmit(); addHistoryResetButton(); }, 150);
    window.setTimeout(function () { fixHistoryFilterSubmit(); addHistoryResetButton(); }, 500);
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

require __DIR__ . '/history.php';
