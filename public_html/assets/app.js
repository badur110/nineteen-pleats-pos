document.addEventListener('change', function (event) {
  if (event.target && event.target.id === 'payment_type') {
    const box = document.getElementById('mixed_fields');
    if (box) box.style.display = event.target.value === 'mixed' ? 'grid' : 'none';
  }
});

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
