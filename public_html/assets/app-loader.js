(function () {
  document.write('<script src="/assets/app.js?core=1&v=23"><\/script>');

  function addCashierHistoryAccess() {
    const nav = document.querySelector('.nav');
    if (nav && !nav.querySelector('a[href*="history"]')) {
      const link = document.createElement('a');
      link.href = '/history';
      link.textContent = 'ისტორია';
      link.className = 'cashier-history-link';
      const logout = nav.querySelector('a[href*="logout"]');
      if (logout) nav.insertBefore(link, logout); else nav.appendChild(link);
    }

    const params = new URLSearchParams(window.location.search);
    const orderId = params.get('order_id');
    const detail = document.querySelector('.history-detail');
    if (!orderId || !detail) return;

    let action = detail.querySelector('[data-reprint-order], a[href*="print_final"]');
    if (detail.textContent.indexOf('ნულით დახურული') !== -1) {
      if (action) action.remove();
      return;
    }

    if (!action) {
      action = document.createElement('a');
      const actions = detail.querySelector('.cashier-history-detail-actions');
      const head = detail.querySelector('.page-head');
      if (actions) actions.insertBefore(action, actions.firstChild);
      else if (head) head.appendChild(action);
      else return;
    }

    action.href = '/print_final?order_id=' + encodeURIComponent(orderId) + '&reprint=1';
    action.className = 'btn success cashier-reprint-button';
    action.setAttribute('data-reprint-order', '1');
    action.textContent = 'ქვითრის ბეჭდვა';
    action.title = 'საბოლოო ქვითრის ხელახლა დაბეჭდვა';
  }

  function runHistoryFixes() {
    addCashierHistoryAccess();
    window.setTimeout(addCashierHistoryAccess, 150);
    window.setTimeout(addCashierHistoryAccess, 700);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runHistoryFixes);
  } else {
    runHistoryFixes();
  }
})();