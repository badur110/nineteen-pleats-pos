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

  function orderIdFromHref(href) {
    try {
      return new URL(href, window.location.origin).searchParams.get('order_id');
    } catch (error) {
      return null;
    }
  }

  function collectHistoryOrderIds() {
    const ids = new Set();
    const current = new URLSearchParams(window.location.search).get('order_id');
    if (current) ids.add(current);
    document.querySelectorAll('a[href*="order_id="]').forEach(function (link) {
      const id = orderIdFromHref(link.href);
      if (id) ids.add(id);
    });
    return Array.from(ids);
  }

  function applyHistoryReceiptNumbers(map) {
    const currentId = new URLSearchParams(window.location.search).get('order_id');
    const detail = document.querySelector('.history-detail');
    if (currentId && detail && map[currentId]) {
      const heading = detail.querySelector('h2');
      if (heading) heading.textContent = 'ქვითარი #' + map[currentId];

      const grid = detail.querySelector('.history-detail-grid, .cashier-history-detail-grid');
      if (grid && !grid.querySelector('[data-receipt-number-card]')) {
        const card = document.createElement('div');
        card.setAttribute('data-receipt-number-card', '1');
        card.innerHTML = '<span>ქვითრის ნომერი</span><strong>#' + map[currentId] + '</strong>';
        grid.insertBefore(card, grid.firstChild);
      }
    }

    document.querySelectorAll('a[href*="order_id="]').forEach(function (link) {
      const id = orderIdFromHref(link.href);
      if (!id || !map[id]) return;
      const row = link.closest('tr');
      if (!row) return;
      const firstCell = row.querySelector('td');
      if (firstCell) {
        firstCell.textContent = '#' + map[id];
        firstCell.title = 'ქვითრის ნომერი';
      }
    });
  }

  function enhanceHistoryReceiptNumbers() {
    const ids = collectHistoryOrderIds();
    if (!ids.length) return;
    fetch('/order_numbers.php?ids=' + encodeURIComponent(ids.join(',')), {cache: 'no-store', credentials: 'same-origin'})
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) { if (data && data.orders) applyHistoryReceiptNumbers(data.orders); })
      .catch(function () {});
  }

  function tableIdFromPage() {
    const parts = window.location.pathname.replace(/^\/+|\/+$/g, '').split('/');
    if (parts[0] === 'table' && /^\d+$/.test(parts[1] || '')) return parts[1];
    const params = new URLSearchParams(window.location.search);
    return params.get('page') === 'table' ? params.get('id') : null;
  }

  function enhanceOpenTableReceiptNumber() {
    const tableId = tableIdFromPage();
    if (!tableId) return;
    fetch('/order_numbers.php?table_id=' + encodeURIComponent(tableId), {cache: 'no-store', credentials: 'same-origin'})
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.open_order || !data.open_order.receipt_number) return;
        const head = document.querySelector('.page-head');
        if (!head || head.querySelector('[data-open-receipt-number]')) return;
        const badge = document.createElement('div');
        badge.setAttribute('data-open-receipt-number', '1');
        badge.className = 'pill';
        badge.style.background = '#2b1b10';
        badge.style.color = '#fff';
        badge.style.fontWeight = '950';
        badge.textContent = 'ქვითარი #' + data.open_order.receipt_number;
        const total = head.querySelector('.total-box');
        if (total) total.insertAdjacentElement('beforebegin', badge); else head.appendChild(badge);
      })
      .catch(function () {});
  }

  function runHistoryFixes() {
    addCashierHistoryAccess();
    enhanceHistoryReceiptNumbers();
    enhanceOpenTableReceiptNumber();
    window.setTimeout(addCashierHistoryAccess, 150);
    window.setTimeout(enhanceHistoryReceiptNumbers, 250);
    window.setTimeout(enhanceOpenTableReceiptNumber, 350);
    window.setTimeout(addCashierHistoryAccess, 700);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runHistoryFixes);
  } else {
    runHistoryFixes();
  }
})();
