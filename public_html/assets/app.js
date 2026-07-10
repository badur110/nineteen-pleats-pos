document.addEventListener('change', function (event) {
  if (event.target && event.target.id === 'payment_type') {
    const box = document.getElementById('mixed_fields');
    if (box) box.style.display = event.target.value === 'mixed' ? 'grid' : 'none';
  }

  if (event.target && event.target.classList.contains('qty-input')) {
    normalizeQtyInput(event.target);
  }
});

document.addEventListener('input', function (event) {
  if (event.target && event.target.classList.contains('qty-input')) {
    event.target.step = '1';
    event.target.min = '1';
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const loginHint = document.querySelector('.login-card .hint');
  if (loginHint) loginHint.remove();

  addHistoryNavLink();
  fixQuantityInputs();

  const params = new URLSearchParams(window.location.search);
  if (params.get('page') === 'products') {
    document.body.classList.add('page-products');
    injectProductPageStyles();
    enhanceProductsPage();
  }
  if (params.get('page') === 'table') {
    document.body.classList.add('page-table');
    enhanceTablePage(params.get('id'));
  }
});

function addHistoryNavLink() {
  const nav = document.querySelector('.nav');
  if (!nav) return;
  if (!nav.querySelector('a[href*="page=products"]')) return;
  if (nav.querySelector('a[href*="page=history"]')) return;

  const historyLink = document.createElement('a');
  historyLink.href = '?page=history';
  historyLink.textContent = 'ისტორია';

  const reportsLink = nav.querySelector('a[href*="page=reports"]');
  if (reportsLink) nav.insertBefore(historyLink, reportsLink);
  else nav.appendChild(historyLink);
}

function fixQuantityInputs() {
  document.querySelectorAll('.qty-input').forEach(function (input) {
    input.step = '1';
    input.min = '1';
    input.inputMode = 'numeric';
    normalizeQtyInput(input);
  });
}

function normalizeQtyInput(input) {
  const number = parseInt(String(input.value).replace(',', '.'), 10);
  input.value = Number.isFinite(number) && number >= 1 ? String(number) : '1';
}

function injectProductPageStyles() {
  if (document.getElementById('product-page-style')) return;
  const style = document.createElement('style');
  style.id = 'product-page-style';
  style.textContent = `
    .page-products .wrap{max-width:1180px}
    .page-products .two-col{grid-template-columns:minmax(300px,360px) minmax(0,1fr);align-items:start;gap:20px}
    .page-products .card{border-radius:24px;overflow:hidden}
    .page-products .card h2{font-size:1.15rem;margin-bottom:16px}
    .page-products .stack label{font-size:.95rem}
    .page-products .stack input{height:46px}
    .page-products .stack .btn{height:46px;border-radius:14px}
    .page-products .table-wrap{border:0;overflow:visible;background:transparent;width:100%}
    .page-products table,.page-products tbody{display:block;width:100%;min-width:0;background:transparent;border-collapse:separate;border-spacing:0}
    .page-products thead{display:none}
    .page-products tbody{display:grid;gap:10px}
    .page-products tr{display:grid;width:100%;grid-template-columns:minmax(150px,1fr) 88px 72px 78px 166px;gap:8px;align-items:center;background:#fff;border:1px solid #ead6bd;border-radius:18px;padding:12px;box-shadow:0 8px 20px rgba(43,27,16,.07);overflow:hidden}
    .page-products td{border:0;padding:0;min-width:0;overflow:hidden;text-overflow:ellipsis}
    .page-products td:nth-child(1){font-weight:900;font-size:1rem;line-height:1.18;white-space:normal;overflow-wrap:anywhere}
    .page-products td:nth-child(2){color:#7a6657;font-size:.92rem;white-space:nowrap}
    .page-products td:nth-child(3){font-weight:900;white-space:nowrap;text-align:left;font-size:.95rem}
    .page-products td:nth-child(4){display:flex;width:100%;max-width:100%;align-items:center;justify-content:center;border-radius:999px;background:#e9ffe4;color:#24733c;font-weight:900;padding:6px 8px;font-size:.84rem;white-space:nowrap;overflow:hidden;text-overflow:clip}
    .page-products .product-actions-cell{display:flex;gap:7px;align-items:center;justify-content:flex-end;flex-wrap:nowrap;min-width:0;overflow:visible}
    .page-products .inline-action-form{margin:0!important;display:inline-flex;min-width:0}
    .page-products .btn.mini{min-height:34px;width:auto!important;padding:7px 9px;border-radius:10px;font-size:.84rem;line-height:1.1;white-space:nowrap}
    .page-products .btn.edit{background:#2357a5;color:#fff!important;text-decoration:none}
    @media(max-width:1120px){.page-products .two-col{grid-template-columns:1fr}.page-products tr{grid-template-columns:minmax(0,1fr) 100px 78px 86px 174px}.page-products .product-actions-cell{justify-content:flex-end}}
    @media(max-width:720px){.page-products tr{grid-template-columns:minmax(0,1fr) 90px 72px 82px}.page-products .product-actions-cell{grid-column:1/-1;justify-content:flex-start}.page-products .btn.mini{min-height:38px}}
    @media(max-width:640px){.page-products tr{display:block;padding:14px}.page-products td{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #f0dfc9;overflow:visible}.page-products td:last-child{border-bottom:0}.page-products td:before{font-weight:900;color:#7a6657}.page-products td:nth-child(1):before{content:'პროდუქტი'}.page-products td:nth-child(2):before{content:'კატეგორია'}.page-products td:nth-child(3):before{content:'ფასი'}.page-products td:nth-child(4):before{content:'სტატუსი'}.page-products td:nth-child(4){justify-content:space-between;background:transparent;color:#24733c;padding:7px 0}.page-products .product-actions-cell{justify-content:stretch;display:flex}.page-products .product-actions-cell,.page-products .inline-action-form,.page-products .product-actions-cell .btn{width:100%!important}.page-products .btn.mini{min-height:40px}}
  `;
  document.head.appendChild(style);
}

function enhanceProductsPage() {
  document.querySelectorAll('td form input[name="action"][value="toggle_product"]').forEach(function (input) {
    const toggleForm = input.closest('form');
    if (!toggleForm || toggleForm.dataset.enhanced === '1') return;
    toggleForm.dataset.enhanced = '1';

    const actionCell = toggleForm.closest('td');
    if (!actionCell) return;
    actionCell.classList.add('product-actions-cell');

    const editLink = actionCell.querySelector('a');
    if (editLink) editLink.classList.add('btn', 'mini', 'edit');

    toggleForm.classList.add('inline-action-form');
    const toggleBtn = toggleForm.querySelector('button');
    if (toggleBtn) toggleBtn.classList.add('mini');
  });
}

function enhanceTablePage(tableId) {
  const totalBox = document.querySelector('.total-box');
  const orderCard = document.querySelector('.pos-grid > .card:nth-child(2)');
  if (!tableId || !totalBox || !orderCard || document.getElementById('zero-close-form')) return;
  if (!orderCard.querySelector('.order-item')) return;

  const totalText = totalBox.textContent.replace(',', '.');
  const total = parseFloat(totalText.replace(/[^0-9.]/g, '')) || 0;
  if (total > 0.001) return;

  const closeTitle = Array.from(orderCard.querySelectorAll('h2')).find(function (h) { return h.textContent.includes('მაგიდის დახურვა'); });
  const closeForm = orderCard.querySelector('form.close-form');
  if (closeTitle) closeTitle.remove();
  if (closeForm) closeForm.remove();

  const form = document.createElement('form');
  form.id = 'zero-close-form';
  form.method = 'post';
  form.className = 'zero-close-form';
  form.innerHTML = '<h3>ცარიელი მაგიდის დახურვა</h3><p class="muted">ჯამი არის 0.00 ₾ — მაგიდა დაიხურება გაყიდვის გარეშე.</p><input type="hidden" name="action" value="cancel_order"><input type="hidden" name="table_id" value="' + escapeHtml(tableId) + '"><button class="btn danger" type="submit">ნულით დახურვა</button>';
  orderCard.appendChild(form);
}

function escapeHtml(text) {
  return String(text).replace(/[&<>"]/g, function (char) {
    return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;'}[char];
  });
}

document.addEventListener('submit', function (event) {
  const qtyInput = event.target.querySelector && event.target.querySelector('.qty-input');
  if (qtyInput) normalizeQtyInput(qtyInput);

  const action = event.target.querySelector && event.target.querySelector('input[name="action"]');
  if (action && action.value === 'cancel_order') {
    if (!confirm('ნამდვილად გინდა ამ მაგიდის ნულით დახურვა? გაყიდვებში თანხა არ დაემატება.')) {
      event.preventDefault();
    }
  }
});

document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-print]');
  if (!button) return;
  const id = button.getAttribute('data-print');
  const el = document.getElementById(id);
  if (!el) return;
  const content = el.innerText;
  const win = window.open('', '_blank', 'width=420,height=720');
  win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title><style>@page{size:80mm auto;margin:4mm}body{font-family:Arial,sans-serif;font-size:13px;line-height:1.35;color:#000;margin:0}pre{white-space:pre-wrap;margin:0;word-break:break-word}</style></head><body><pre>' + escapeHtml(content) + '</pre><script>window.onload=function(){window.print();}</script></body></html>');
  win.document.close();
});
