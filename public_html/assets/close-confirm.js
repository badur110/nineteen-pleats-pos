document.addEventListener('DOMContentLoaded', function () {
  injectGarbaliaWorkflowStyles();
  markOrderItemStates();
  enhanceDayCashMovementPanel();
  enhanceHistoryPage();
});

function injectGarbaliaWorkflowStyles() {
  if (document.getElementById('garbalia-workflow-style')) return;
  const style = document.createElement('style');
  style.id = 'garbalia-workflow-style';
  style.textContent = `
    .table-card.pending{background:linear-gradient(135deg,#fff7df 0%,#ffe6aa 100%)!important;border-color:#d99a26!important}
    .table-card.pending strong{color:#6b3f00;background:rgba(255,255,255,.76)!important}
    .current-order-card .order-item{padding:11px 12px!important;margin-bottom:10px!important;border-radius:16px!important;box-shadow:0 7px 16px rgba(43,27,16,.055)!important}
    .current-order-card .order-item>div:first-child{padding:0!important}
    .current-order-card .order-item strong{font-size:.98rem!important;line-height:1.2!important}
    .current-order-card .order-item small{font-size:.82rem!important;line-height:1.25!important}
    .order-item.unsent-item{border-left:6px solid #d99020!important;background:linear-gradient(90deg,rgba(255,243,205,.78),#fffaf2 55%)!important}
    .order-item.sent-item{border-left:6px solid #24733c!important;background:linear-gradient(90deg,rgba(233,255,228,.78),#fffaf2 55%)!important}
    .unsent-text{display:inline-flex!important;width:max-content!important;margin-top:6px!important;border-radius:999px!important;background:#fff3cd!important;color:#8a5600!important;font-weight:950!important;padding:4px 9px!important}
    .sent{display:inline-flex!important;width:max-content!important;margin-top:6px!important;border-radius:999px!important;background:#e9ffe4!important;color:#24733c!important;font-weight:950!important;padding:4px 9px!important}
    .page-table .cancel-form.cancel-form-compact{grid-template-columns:minmax(170px,1fr) 112px!important;gap:8px!important;margin-top:2px!important}
    .page-table .cancel-form.cancel-form-compact select{min-height:40px!important;border-radius:12px!important}
    .page-table .cancel-form.cancel-form-compact .btn{min-height:40px!important;border-radius:12px!important;white-space:nowrap!important}
    .garbalia-confirm-info .order-line strong{white-space:normal!important;text-align:right}
    .garbalia-cash-actions-panel{margin:18px 0!important;padding:18px 20px!important;border-radius:24px!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:18px!important;background:linear-gradient(135deg,rgba(255,250,242,.97),rgba(241,226,206,.76))!important;box-shadow:0 18px 42px rgba(43,27,16,.10)!important}
    .garbalia-cash-panel-copy{display:flex;align-items:center;gap:14px;min-width:0}.garbalia-cash-panel-copy h2{margin:0;font-size:1.12rem;font-weight:950;color:#2b1b10}.garbalia-cash-panel-icon{display:grid;place-items:center;width:46px;height:46px;border-radius:16px;background:#2b1b10;color:#fff;font-size:1.2rem;font-weight:950;box-shadow:0 10px 22px rgba(43,27,16,.16)}.garbalia-cash-panel-actions{display:flex;gap:10px;align-items:center;justify-content:flex-end;flex-wrap:wrap}.garbalia-cash-panel-actions .btn{min-height:46px;border-radius:15px!important;white-space:nowrap!important}.garbalia-day-close-card{margin-top:16px!important;margin-bottom:18px!important;max-width:640px!important}
    .garbalia-cash-modal[hidden]{display:none!important}.garbalia-cash-modal{position:fixed;inset:0;z-index:10020;display:grid;place-items:center;padding:20px;background:rgba(43,27,16,.46);backdrop-filter:blur(7px);animation:garbaliaFadeIn .16s ease-out}.garbalia-cash-dialog{position:relative;width:min(520px,100%);max-height:min(88vh,760px);overflow:auto;border:1px solid #ead6bd;border-radius:28px;background:linear-gradient(180deg,#fffaf2 0%,#f8ecdd 100%);box-shadow:0 28px 70px rgba(43,27,16,.30);padding:28px;color:#2b1b10;text-align:center;animation:garbaliaPopIn .18s ease-out}.garbalia-cash-history-dialog{width:min(760px,100%);text-align:left;overflow-y:auto!important;overflow-x:hidden!important}.garbalia-cash-bg-logo{position:absolute;right:-18px;top:-16px;width:136px;height:88px;object-fit:contain;opacity:.07;filter:brightness(0);pointer-events:none}.garbalia-cash-mini{width:48px;height:34px;object-fit:contain;margin:0 auto 10px;display:block;mix-blend-mode:multiply}.garbalia-cash-dialog h2,.garbalia-cash-dialog h3{margin:0 0 8px;font-size:1.35rem;font-weight:950;letter-spacing:-.02em}.garbalia-cash-dialog p{margin:0 auto 18px;max-width:390px;color:#6d5140;font-weight:800;line-height:1.45}.garbalia-cash-close{position:absolute;right:12px;top:12px;width:34px;height:34px;border:0;border-radius:50%;background:rgba(43,27,16,.08);color:#2b1b10;font-size:20px;font-weight:900;cursor:pointer}.garbalia-cash-popup-form{display:grid!important;gap:13px!important;text-align:left}.garbalia-cash-popup-form label{margin:0!important}.garbalia-cash-popup-form input,.garbalia-cash-popup-form select{min-height:48px!important;border-radius:14px!important}.garbalia-cash-popup-form .btn{min-height:48px!important;border-radius:14px!important;width:100%!important}.garbalia-cash-history-dialog .table-wrap{margin-top:12px;max-height:52vh;overflow-y:auto!important;overflow-x:hidden!important;width:100%!important}.garbalia-cash-history-dialog table{width:100%!important;min-width:0!important;table-layout:fixed!important}.garbalia-cash-history-dialog th,.garbalia-cash-history-dialog td{white-space:normal!important;word-break:break-word!important;overflow-wrap:anywhere!important}
    .history-filters .history-grid{grid-template-columns:repeat(4,minmax(150px,1fr))!important}.history-clean-actions{display:flex!important;align-items:center!important;gap:6px!important;flex-wrap:nowrap!important;margin-top:12px!important;overflow:visible!important}.history-clean-actions .btn{white-space:nowrap!important;font-size:.86rem!important;padding:9px 12px!important;min-height:40px!important;border-radius:12px!important}.history-detail.only-detail{max-width:980px;margin:0 auto 22px!important}.history-detail.only-detail .page-head{margin-bottom:14px!important}.history-detail.only-detail h3{margin-top:20px!important}.history-detail.only-detail .table-wrap{margin-top:10px!important}
    @media(max-width:1050px){.history-clean-actions{flex-wrap:wrap!important}.history-clean-actions .btn{font-size:.85rem!important}}
    @media(max-width:820px){.page-table .cancel-form.cancel-form-compact{grid-template-columns:1fr!important}.current-order-card .order-item{padding:12px!important}.garbalia-cash-actions-panel{align-items:stretch!important;flex-direction:column!important}.garbalia-cash-panel-actions{display:grid;grid-template-columns:1fr;width:100%}.garbalia-cash-panel-actions .btn{width:100%!important}.history-filters .history-grid{grid-template-columns:1fr 1fr!important}.history-clean-actions{flex-wrap:wrap!important}}
    @media(max-width:560px){.history-filters .history-grid{grid-template-columns:1fr!important}.history-clean-actions .btn{width:100%!important}}
  `;
  document.head.appendChild(style);
}

function markOrderItemStates() {
  document.querySelectorAll('.order-item:not(.cancelled)').forEach(function (item) {
    if (item.querySelector('.sent')) item.classList.add('sent-item');
    if (item.querySelector('.unsent-text') || !item.querySelector('.sent')) item.classList.add('unsent-item');
  });
}

function enhanceDayCashMovementPanel() {
  const layout = document.querySelector('.day-cash-layout');
  if (!layout || layout.dataset.garbaliaEnhanced === '1') return;
  layout.dataset.garbaliaEnhanced = '1';

  const cards = Array.from(layout.querySelectorAll('.card'));
  const cashCard = cards[0];
  const historyCard = cards[1];
  const form = cashCard ? cashCard.querySelector('form') : null;
  if (!form || !historyCard) return;

  const historyHtml = historyCard.innerHTML;
  const movementCount = historyCard.querySelectorAll('tbody tr').length;

  const panel = document.createElement('section');
  panel.className = 'card garbalia-cash-actions-panel';
  panel.innerHTML = '<div class="garbalia-cash-panel-copy"><span class="garbalia-cash-panel-icon">₾</span><div><h2>სალაროს მოძრაობა</h2></div></div><div class="garbalia-cash-panel-actions"><button type="button" class="btn success" data-cash-open>თანხის მოძრაობის დამატება</button><button type="button" class="btn" data-cash-history>მოძრაობის ნახვა' + (movementCount ? ' · ' + movementCount : '') + '</button></div>';
  layout.insertAdjacentElement('beforebegin', panel);

  const movementModal = document.createElement('div');
  movementModal.id = 'garbalia-cash-movement-modal';
  movementModal.className = 'garbalia-cash-modal';
  movementModal.hidden = true;
  movementModal.innerHTML = '<div class="garbalia-cash-dialog" role="dialog" aria-modal="true"><button type="button" class="garbalia-cash-close" data-cash-close aria-label="დახურვა">×</button><img class="garbalia-cash-bg-logo" src="/Logo.png?v=12" alt=""><img class="garbalia-cash-mini" src="/Logo.png?v=12" alt="GARBALIA"><h3>სალაროს მოძრაობა</h3><p>აირჩიე ტიპი, ჩაწერე თანხა და საჭიროების შემთხვევაში კომენტარი.</p><div class="garbalia-cash-form-holder"></div></div>';
  movementModal.querySelector('.garbalia-cash-form-holder').appendChild(form);
  form.classList.add('garbalia-cash-popup-form');
  const submit = form.querySelector('button[type="submit"], button:not([type])');
  if (submit) submit.textContent = 'დამატება';
  document.body.appendChild(movementModal);

  const historyModal = document.createElement('div');
  historyModal.id = 'garbalia-cash-history-modal';
  historyModal.className = 'garbalia-cash-modal';
  historyModal.hidden = true;
  historyModal.innerHTML = '<div class="garbalia-cash-dialog garbalia-cash-history-dialog" role="dialog" aria-modal="true"><button type="button" class="garbalia-cash-close" data-cash-close aria-label="დახურვა">×</button><img class="garbalia-cash-bg-logo" src="/Logo.png?v=12" alt=""><img class="garbalia-cash-mini" src="/Logo.png?v=12" alt="GARBALIA">' + historyHtml + '</div>';
  document.body.appendChild(historyModal);

  layout.remove();

  const closeDayCard = Array.from(document.querySelectorAll('.card.narrow')).find(function (card) {
    const h2 = card.querySelector('h2');
    return h2 && h2.textContent.includes('დღის დახურვა');
  });
  if (closeDayCard) closeDayCard.classList.add('garbalia-day-close-card');

  panel.querySelector('[data-cash-open]').addEventListener('click', function () {
    openCashModal(movementModal);
    const amount = movementModal.querySelector('input[name="amount"]');
    if (amount) window.setTimeout(function () { amount.focus(); }, 80);
  });
  panel.querySelector('[data-cash-history]').addEventListener('click', function () { openCashModal(historyModal); });

  document.querySelectorAll('[data-cash-close]').forEach(function (btn) {
    btn.addEventListener('click', function () { closeCashModal(btn.closest('.garbalia-cash-modal')); });
  });
  [movementModal, historyModal].forEach(function (modal) {
    modal.addEventListener('click', function (event) { if (event.target === modal) closeCashModal(modal); });
  });
}

function openCashModal(modal) {
  if (!modal) return;
  modal.hidden = false;
}

function closeCashModal(modal) {
  if (!modal) return;
  modal.hidden = true;
}

document.addEventListener('keydown', function (event) {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('.garbalia-cash-modal:not([hidden])').forEach(function (modal) { closeCashModal(modal); });
});

function isHistoryPage() {
  const params = new URLSearchParams(window.location.search);
  const path = window.location.pathname.replace(/^\/+|\/+$/g, '');
  return params.get('page') === 'history' || path === 'history';
}

function formatDateForUrl(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return y + '-' + m + '-' + d;
}

function historyUrl(from, to) {
  const params = new URLSearchParams(window.location.search);
  params.delete('page');
  params.delete('order_id');
  params.delete('payment');
  params.delete('status');
  params.delete('item_filter');
  params.delete('product');
  params.delete('export');
  params.set('from', from);
  params.set('to', to);
  const query = params.toString();
  return '/history' + (query ? '?' + query : '');
}

function removeHistoryFilterByLabel(text) {
  document.querySelectorAll('.history-filters label').forEach(function (label) {
    const ownText = Array.from(label.childNodes)
      .filter(function (node) { return node.nodeType === Node.TEXT_NODE; })
      .map(function (node) { return node.textContent.trim(); })
      .join(' ');
    if (ownText.includes(text) || label.textContent.trim().startsWith(text)) label.remove();
  });
}

function simplifyHistoryListTable() {
  const cards = Array.from(document.querySelectorAll('section.card'));
  const listCard = cards.find(function (card) {
    const h2 = card.querySelector('h2');
    return h2 && h2.textContent.trim().includes('ანგარიშების სია');
  });
  if (!listCard) return;
  const table = listCard.querySelector('table');
  if (!table || table.dataset.garbaliaHistoryClean === '1') return;
  table.dataset.garbaliaHistoryClean = '1';

  const headRow = table.querySelector('thead tr');
  if (headRow && headRow.children.length >= 9) {
    const heads = Array.from(headRow.children);
    headRow.innerHTML = '';
    [heads[7], heads[0], heads[2], heads[3], heads[4], heads[5], heads[6], heads[8]].forEach(function (cell) { headRow.appendChild(cell); });
  }

  table.querySelectorAll('tbody tr').forEach(function (row) {
    const cells = Array.from(row.children);
    if (cells.length < 9) return;
    row.innerHTML = '';
    [cells[7], cells[0], cells[2], cells[3], cells[4], cells[5], cells[6], cells[8]].forEach(function (cell) { row.appendChild(cell); });
  });
}

function enhanceHistoryPage() {
  if (!isHistoryPage()) return;
  const params = new URLSearchParams(window.location.search);
  const orderId = params.get('order_id');

  if (orderId) {
    document.querySelectorAll('.history-filters, .stats').forEach(function (el) { el.remove(); });
    const mainHead = document.querySelector('.wrap > .page-head');
    if (mainHead) mainHead.remove();
    Array.from(document.querySelectorAll('section.card')).forEach(function (card) {
      const h2 = card.querySelector('h2');
      if (h2 && h2.textContent.trim().includes('ანგარიშების სია')) card.remove();
    });
    const detail = document.querySelector('.history-detail');
    if (detail) {
      detail.classList.add('only-detail');
      const back = detail.querySelector('.page-head .btn');
      if (back) {
        back.href = '/history';
        back.textContent = 'ისტორიაში დაბრუნება';
      }
    }
    return;
  }

  removeHistoryFilterByLabel('პროდუქტის ძებნა');
  removeHistoryFilterByLabel('გადახდა');
  removeHistoryFilterByLabel('სტატუსი');
  removeHistoryFilterByLabel('პროდუქტები');

  const actions = document.querySelector('.history-actions');
  if (actions && !actions.dataset.garbaliaExtraFilters) {
    actions.dataset.garbaliaExtraFilters = '1';
    actions.classList.add('history-clean-actions');

    const searchBtn = document.querySelector('.history-filters form button[type="submit"], .history-filters form button.btn.primary');
    if (searchBtn) {
      searchBtn.classList.add('primary');
      searchBtn.textContent = 'ძებნა';
      actions.insertBefore(searchBtn, actions.firstChild);
    }

    const today = new Date();
    const last7 = new Date(today);
    last7.setDate(today.getDate() - 6);
    const prevMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const prevMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    const yearStart = new Date(today.getFullYear(), 0, 1);
    const buttons = [
      ['წინა თვეში', historyUrl(formatDateForUrl(prevMonthStart), formatDateForUrl(prevMonthEnd))],
      ['ამ წელში', historyUrl(formatDateForUrl(yearStart), formatDateForUrl(today))],
      ['ბოლო 7 დღეში', historyUrl(formatDateForUrl(last7), formatDateForUrl(today))]
    ];
    const excel = actions.querySelector('a[href*="export=excel"]');
    buttons.forEach(function (item) {
      const a = document.createElement('a');
      a.className = 'btn';
      a.href = item[1];
      a.textContent = item[0];
      if (excel) actions.insertBefore(a, excel); else actions.appendChild(a);
    });
  }

  const pageHint = document.querySelector('.wrap > .page-head .muted');
  if (pageHint) pageHint.textContent = 'აირჩიე პერიოდი, მაგიდა ან მომხმარებელი — შემდეგ გახსენი კონკრეტული ანგარიში.';

  simplifyHistoryListTable();
}

function activeOrderItems() {
  return Array.from(document.querySelectorAll('.order-item:not(.cancelled)'));
}

function unsentOrderItems() {
  return activeOrderItems().filter(function (item) { return !item.querySelector('.sent'); });
}

function itemTitle(item, fallback) {
  return item && item.querySelector('strong') ? item.querySelector('strong').textContent.trim() : fallback;
}

function itemSum(item) {
  const sumLine = item && item.querySelector('small') ? item.querySelector('small').textContent.trim().replace(/\s+/g, ' ') : '';
  return sumLine.replace(/^.*ჯამი:\s*/u, '').trim();
}

document.addEventListener('submit', function (event) {
  const form = event.target;
  const action = form && form.querySelector ? form.querySelector('input[name="action"]') : null;
  if (!action) return;

  if (action.value === 'send_order') {
    if (form.dataset.garbaliaConfirmedSend === '1') return;
    event.preventDefault();
    event.stopPropagation();

    const items = unsentOrderItems();
    const tableTitle = document.querySelector('.page-head h1') ? document.querySelector('.page-head h1').textContent.trim() : 'მაგიდა';
    const info = [{label: 'მაგიდა', value: tableTitle}, {label: 'გასაგზავნი', value: String(items.length) + ' პროდუქტი'}];
    items.slice(0, 8).forEach(function (item, index) {
      const sum = itemSum(item);
      info.push({label: 'პროდუქტი ' + (index + 1), value: sum ? itemTitle(item, 'პროდუქტი') + ' — ' + sum : itemTitle(item, 'პროდუქტი'), className: 'order-line'});
    });
    if (items.length > 8) info.push({label: 'კიდევ', value: '+' + (items.length - 8) + ' პროდუქტი'});

    showGarbaliaConfirm({
      id: 'garbalia-send-order-modal',
      title: 'შეკვეთის გაგზავნა',
      message: 'გადაამოწმე გასაგზავნი პროდუქცია. დადასტურების შემდეგ შეკვეთა გადავა ბეჭდვაზე.',
      info: info,
      confirmText: 'დიახ, გაგზავნა',
      cancelText: 'არა',
      confirmClass: 'primary',
      onConfirm: function () {
        form.dataset.garbaliaConfirmedSend = '1';
        garbaliaAllowNavigation = true;
        form.submit();
      }
    });
    return;
  }

  if (action.value !== 'close_order') return;
  if (form.dataset.garbaliaConfirmedClose === '1') return;

  event.preventDefault();
  event.stopPropagation();

  const unsent = unsentOrderItems();
  if (unsent.length > 0) {
    const info = [{label: 'გასაგზავნი', value: String(unsent.length) + ' პროდუქტი'}];
    unsent.slice(0, 6).forEach(function (item, index) {
      info.push({label: 'პროდუქტი ' + (index + 1), value: itemTitle(item, 'პროდუქტი'), className: 'order-line'});
    });
    if (unsent.length > 6) info.push({label: 'კიდევ', value: '+' + (unsent.length - 6) + ' პროდუქტი'});
    showGarbaliaConfirm({
      id: 'garbalia-block-close-modal',
      title: 'ჯერ გაგზავნაა საჭირო',
      message: 'ამ მაგიდაზე არის გაუგზავნელი პროდუქცია. მაგიდის დახურვამდე ჯერ გაგზავნე შეკვეთა.',
      info: info,
      confirmText: 'შეკვეთის გაგზავნა',
      cancelText: 'დახურვა',
      confirmClass: 'primary',
      onConfirm: function () {
        const sendForm = document.querySelector('form.send-order-form, form input[name="action"][value="send_order"]')?.closest('form');
        if (sendForm) sendForm.requestSubmit ? sendForm.requestSubmit() : sendForm.submit();
      }
    });
    return;
  }

  const items = activeOrderItems();
  const totalText = document.querySelector('.total-box') ? document.querySelector('.total-box').textContent.trim() : '0.00 ₾';
  const paymentSelect = form.querySelector('select[name="payment_type"]');
  const paymentText = paymentSelect ? paymentSelect.options[paymentSelect.selectedIndex].text.trim() : '—';
  const tableTitle = document.querySelector('.page-head h1') ? document.querySelector('.page-head h1').textContent.trim() : 'მაგიდა';

  const info = [];
  info.push({label: 'მაგიდა', value: tableTitle});
  info.push({label: 'გადახდა', value: paymentText});
  info.push({label: 'საბოლოო ჯამი', value: totalText, className: 'diff-zero'});

  items.slice(0, 8).forEach(function (item, index) {
    const sum = itemSum(item);
    info.push({
      label: 'პროდუქტი ' + (index + 1),
      value: sum ? (itemTitle(item, 'პროდუქტი') + ' — ' + sum) : itemTitle(item, 'პროდუქტი'),
      className: 'order-line'
    });
  });

  if (items.length > 8) {
    info.push({label: 'კიდევ', value: '+' + (items.length - 8) + ' პროდუქტი'});
  }

  showGarbaliaConfirm({
    id: 'garbalia-close-order-modal',
    title: 'მაგიდის დახურვა',
    message: 'გადაამოწმე შეკვეთის სია და საბოლოო თანხა. დადასტურების შემდეგ მაგიდა დაიხურება.',
    info: info,
    confirmText: 'დიახ, დახურვა',
    cancelText: 'გაუქმება',
    confirmClass: 'success',
    onConfirm: function () {
      form.dataset.garbaliaConfirmedClose = '1';
      garbaliaAllowNavigation = true;
      form.submit();
    }
  });
}, true);