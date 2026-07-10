document.addEventListener('DOMContentLoaded', function () {
  injectGarbaliaWorkflowStyles();
  markOrderItemStates();
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
    .day-cash-layout{margin-bottom:18px!important}
    .garbalia-confirm-info .order-line strong{white-space:normal!important;text-align:right}
    @media(max-width:820px){.page-table .cancel-form.cancel-form-compact{grid-template-columns:1fr!important}.current-order-card .order-item{padding:12px!important}}
  `;
  document.head.appendChild(style);
}

function markOrderItemStates() {
  document.querySelectorAll('.order-item:not(.cancelled)').forEach(function (item) {
    if (item.querySelector('.sent')) item.classList.add('sent-item');
    if (item.querySelector('.unsent-text') || !item.querySelector('.sent')) item.classList.add('unsent-item');
  });
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