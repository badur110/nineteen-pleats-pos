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
    if (!orderId || !detail || detail.querySelector('[data-reprint-order]')) return;
    if (detail.textContent.indexOf('ნულით დახურული') !== -1) return;

    const head = detail.querySelector('.page-head');
    if (!head) return;
    const action = document.createElement('a');
    action.href = '/print_final?order_id=' + encodeURIComponent(orderId) + '&reprint=1';
    action.className = 'btn success';
    action.setAttribute('data-reprint-order', '1');
    action.textContent = 'ქვითრის ხელახლა ბეჭდვა';
    head.appendChild(action);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addCashierHistoryAccess);
  } else {
    addCashierHistoryAccess();
  }
})();
