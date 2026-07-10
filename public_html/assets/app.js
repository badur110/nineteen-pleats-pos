document.addEventListener('change', function (event) {
  if (event.target && event.target.id === 'payment_type') {
    const box = document.getElementById('mixed_fields');
    if (box) box.style.display = event.target.value === 'mixed' ? 'grid' : 'none';
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const loginHint = document.querySelector('.login-card .hint');
  if (loginHint) loginHint.remove();

  const params = new URLSearchParams(window.location.search);
  if (params.get('page') === 'products') {
    document.body.classList.add('page-products');
    injectProductPageStyles();
    enhanceProductsPage();
  }
});

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
    .page-products tr{display:grid;width:100%;grid-template-columns:minmax(180px,1fr) 96px 78px 96px 205px;gap:12px;align-items:center;background:#fff;border:1px solid #ead6bd;border-radius:18px;padding:12px;box-shadow:0 8px 20px rgba(43,27,16,.07)}
    .page-products td{border:0;padding:0;min-width:0;overflow-wrap:anywhere}
    .page-products td:nth-child(1){font-weight:900;font-size:1.02rem;line-height:1.15}
    .page-products td:nth-child(2){color:#7a6657;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .page-products td:nth-child(3){font-weight:900;white-space:nowrap;text-align:left}
    .page-products td:nth-child(4){display:flex;justify-self:start;align-items:center;justify-content:center;min-width:86px;max-width:96px;border-radius:999px;background:#e9ffe4;color:#24733c;font-weight:900;padding:7px 10px;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .page-products .product-actions-cell{display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:nowrap;min-width:0}
    .page-products .inline-action-form{margin:0!important;display:inline-flex}
    .page-products .btn.mini{min-height:36px;width:auto!important;padding:8px 11px;border-radius:11px;font-size:.86rem;line-height:1.1;white-space:nowrap}
    .page-products .btn.edit{background:#2357a5;color:#fff!important;text-decoration:none}
    @media(max-width:1120px){.page-products .two-col{grid-template-columns:1fr}.page-products tr{grid-template-columns:minmax(0,1fr) 110px 80px 96px 205px}}
    @media(max-width:820px){.page-products tr{grid-template-columns:minmax(0,1fr) 90px 76px 96px}.page-products .product-actions-cell{grid-column:1/-1;justify-content:flex-start}}
    @media(max-width:640px){.page-products tr{display:block;padding:14px}.page-products td{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #f0dfc9}.page-products td:last-child{border-bottom:0}.page-products td:before{font-weight:900;color:#7a6657}.page-products td:nth-child(1):before{content:'პროდუქტი'}.page-products td:nth-child(2):before{content:'კატეგორია'}.page-products td:nth-child(3):before{content:'ფასი'}.page-products td:nth-child(4):before{content:'სტატუსი'}.page-products td:nth-child(4){max-width:none;width:100%;justify-content:space-between;background:transparent;color:inherit;padding:7px 0}.page-products td:nth-child(4)::after{content:attr(data-status);border-radius:999px;background:#e9ffe4;color:#24733c;font-weight:900;padding:6px 10px}.page-products .product-actions-cell{justify-content:stretch;display:flex}.page-products .product-actions-cell,.page-products .inline-action-form,.page-products .product-actions-cell .btn{width:100%!important}.page-products .btn.mini{min-height:40px}}
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

    const row = actionCell.closest('tr');
    if (row && row.children[3]) {
      row.children[3].setAttribute('data-status', row.children[3].textContent.trim());
    }

    const editLink = actionCell.querySelector('a');
    if (editLink) editLink.classList.add('btn', 'mini', 'edit');

    toggleForm.classList.add('inline-action-form');
    const toggleBtn = toggleForm.querySelector('button');
    if (toggleBtn) toggleBtn.classList.add('mini');
  });
}

function escapeHtml(text) {
  return String(text).replace(/[&<>"]/g, function (char) {
    return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;'}[char];
  });
}

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
