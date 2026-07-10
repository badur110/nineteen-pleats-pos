document.addEventListener('DOMContentLoaded', function () {
  injectQuantityEditStyles();
  enhanceUnsentQuantityControls();
});

function injectQuantityEditStyles() {
  if (document.getElementById('garbalia-qty-edit-style')) return;
  const style = document.createElement('style');
  style.id = 'garbalia-qty-edit-style';
  style.textContent = `
    .order-qty-edit{display:inline-flex!important;align-items:center!important;gap:7px!important;margin-top:9px!important;padding:6px 8px!important;border-radius:14px!important;background:rgba(255,255,255,.72)!important;border:1px solid rgba(217,144,32,.30)!important;box-shadow:0 6px 14px rgba(43,27,16,.06)!important}
    .order-qty-edit label{display:inline-flex!important;align-items:center!important;gap:6px!important;margin:0!important;color:#8a5600!important;font-size:.78rem!important;font-weight:950!important;white-space:nowrap!important}
    .order-qty-edit input[type="number"]{width:64px!important;min-height:34px!important;height:34px!important;padding:4px 6px!important;border-radius:10px!important;text-align:center!important;font-size:.95rem!important;font-weight:950!important;background:#fffaf2!important;border:1px solid #d6b48c!important;color:#2b1b10!important}
    .order-qty-edit button{width:36px!important;height:34px!important;min-height:34px!important;border-radius:10px!important;border:0!important;background:#24733c!important;color:#fff!important;font-weight:950!important;box-shadow:0 6px 12px rgba(36,115,60,.16)!important;cursor:pointer!important;padding:0!important}
    .order-qty-edit button:hover{filter:brightness(.96)!important;transform:translateY(-1px)!important}
    @media(max-width:620px){.order-qty-edit{display:flex!important;width:max-content!important;max-width:100%!important}.order-qty-edit input[type="number"]{width:58px!important}}
  `;
  document.head.appendChild(style);
}

function quantityFromOrderTitle(text) {
  const match = String(text || '').trim().match(/^(\d+(?:[\.,]\d+)?)\s*x\s+/i);
  if (!match) return '1';
  const parsed = parseInt(match[1].replace(',', '.'), 10);
  return Number.isFinite(parsed) && parsed >= 1 ? String(parsed) : '1';
}

function enhanceUnsentQuantityControls() {
  document.querySelectorAll('.current-order-card .order-item.unsent-item').forEach(function (item) {
    if (item.dataset.qtyEnhanced === '1') return;
    item.dataset.qtyEnhanced = '1';

    const deleteForm = item.querySelector('form.order-delete-form, form.cancel-form');
    if (!deleteForm) return;
    const itemId = deleteForm.querySelector('input[name="item_id"]');
    const tableId = deleteForm.querySelector('input[name="table_id"]');
    if (!itemId || !tableId) return;

    const title = item.querySelector('strong') ? item.querySelector('strong').textContent : '';
    const qty = quantityFromOrderTitle(title);

    const form = document.createElement('form');
    form.className = 'order-qty-edit';
    form.method = 'post';
    form.innerHTML =
      '<input type="hidden" name="action" value="update_item_quantity">' +
      '<input type="hidden" name="table_id" value="' + tableId.value + '">' +
      '<input type="hidden" name="item_id" value="' + itemId.value + '">' +
      '<label>რაოდ.<input type="number" name="quantity" min="1" step="1" value="' + qty + '" inputmode="numeric"></label>' +
      '<button type="submit" title="რაოდენობის შეცვლა" aria-label="რაოდენობის შეცვლა">✓</button>';

    const infoBox = item.querySelector('div:first-child') || item;
    const badge = item.querySelector('.unsent-text');
    if (badge) badge.insertAdjacentElement('afterend', form);
    else infoBox.appendChild(form);

    form.addEventListener('submit', function () {
      if (typeof garbaliaAllowNavigation !== 'undefined') garbaliaAllowNavigation = true;
      const input = form.querySelector('input[name="quantity"]');
      if (input) {
        const value = parseInt(String(input.value).replace(',', '.'), 10);
        input.value = Number.isFinite(value) && value >= 1 ? String(value) : '1';
      }
    });
  });
}
