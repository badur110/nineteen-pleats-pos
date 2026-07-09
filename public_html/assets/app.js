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
    enhanceProductsPage();
  }
});

function enhanceProductsPage() {
  document.querySelectorAll('td form input[name="action"][value="toggle_product"]').forEach(function (input) {
    const toggleForm = input.closest('form');
    if (!toggleForm || toggleForm.dataset.enhanced === '1') return;
    toggleForm.dataset.enhanced = '1';

    const idInput = toggleForm.querySelector('input[name="id"]');
    if (!idInput) return;

    const actionCell = toggleForm.closest('td');
    if (!actionCell) return;
    actionCell.classList.add('product-actions-cell');

    const editLink = actionCell.querySelector('a');
    if (editLink) editLink.classList.add('btn', 'mini', 'edit');

    toggleForm.classList.add('inline-action-form');
    const toggleBtn = toggleForm.querySelector('button');
    if (toggleBtn) toggleBtn.classList.add('mini');

    const deleteForm = document.createElement('form');
    deleteForm.method = 'post';
    deleteForm.className = 'inline-action-form';
    deleteForm.innerHTML = '<input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="' + escapeHtml(idInput.value) + '"><button class="btn danger mini" type="submit">წაშლა</button>';
    actionCell.appendChild(deleteForm);
  });
}

function escapeHtml(text) {
  return String(text).replace(/[&<>"]/g, function (char) {
    return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;'}[char];
  });
}

document.addEventListener('submit', function (event) {
  const form = event.target;
  const action = form.querySelector('input[name="action"]');
  if (action && action.value === 'delete_product') {
    if (!confirm('ნამდვილად გინდა პროდუქტის წაშლა? თუ პროდუქტი უკვე გამოყენებულია შეკვეთებში, ისტორიისთვის მხოლოდ გაითიშება.')) {
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
