(function () {
  function addReceiptSettingsNav() {
    const nav = document.querySelector('.nav');
    if (!nav || !nav.querySelector('a[href*="products"]') || nav.querySelector('a[href*="receipts"]')) return;
    const link = document.createElement('a');
    link.href = '/receipts';
    link.textContent = 'ქვითრები';
    const statistics = nav.querySelector('a[href*="statistics"]');
    const history = nav.querySelector('a[href*="history"]');
    if (statistics) nav.insertBefore(link, statistics);
    else if (history) nav.insertBefore(link, history);
    else nav.appendChild(link);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }

  function unsentItems() {
    return Array.from(document.querySelectorAll('.current-order-card .order-item:not(.cancelled)')).filter(function (item) {
      return !item.querySelector('.sent') && !item.classList.contains('sent-item');
    });
  }

  function orderInfoRows() {
    const rows = [];
    const table = document.querySelector('.page-head h1');
    const items = unsentItems();
    rows.push({label: 'მაგიდა', value: table ? table.textContent.trim() : '—'});
    rows.push({label: 'გასაგზავნი', value: items.length + ' პროდუქტი'});
    items.slice(0, 8).forEach(function (item, index) {
      const name = item.querySelector('strong');
      rows.push({label: 'პროდუქტი ' + (index + 1), value: name ? name.textContent.trim() : 'პროდუქტი', className: 'order-line'});
    });
    if (items.length > 8) rows.push({label: 'დამატებით', value: '+' + (items.length - 8) + ' პროდუქტი'});
    return rows;
  }

  function loadingWindow(win) {
    win.document.open();
    win.document.write('<!doctype html><html lang="ka"><head><meta charset="utf-8"><title>ბეჭდვა</title><style>body{margin:0;display:grid;place-items:center;min-height:100vh;background:#f6efe4;color:#2b1b10;font-family:Arial,sans-serif;text-align:center}div{padding:24px}strong{display:block;font-size:20px;margin-bottom:8px}span{color:#7a6657}</style></head><body><div><strong>ქვითრები მზადდება…</strong><span>ბეჭდვის ფანჯარა რამდენიმე წამში გაიხსნება.</span></div></body></html>');
    win.document.close();
  }

  function printDocument(data) {
    const barText = escapeHtml(data.bar && data.bar.text ? data.bar.text : '');
    const kitchenText = escapeHtml(data.kitchen && data.kitchen.text ? data.kitchen.text : '');
    const barSize = Math.max(10, Math.min(18, Number(data.bar && data.bar.font_size) || 13));
    const kitchenSize = Math.max(10, Math.min(18, Number(data.kitchen && data.kitchen.font_size) || 14));

    return '<!doctype html><html lang="ka"><head><meta charset="utf-8"><title>ქვითარი #' + escapeHtml(data.receipt_number || '') + '</title><style>' +
      '@page{size:80mm auto;margin:3mm}' +
      'html,body{margin:0;padding:0;background:#fff;color:#000}' +
      'body{width:74mm;font-family:Arial,"Noto Sans Georgian",sans-serif}' +
      '.receipt{box-sizing:border-box;width:100%;padding:0 0 3mm;break-after:page;page-break-after:always}' +
      '.receipt:last-child{break-after:auto;page-break-after:auto}' +
      'pre{margin:0;white-space:pre-wrap;word-break:break-word;font-family:Arial,"Noto Sans Georgian",sans-serif;line-height:1.35}' +
      '</style></head><body>' +
      '<section class="receipt"><pre style="font-size:' + barSize + 'px">' + barText + '</pre></section>' +
      '<section class="receipt"><pre style="font-size:' + kitchenSize + 'px">' + kitchenText + '</pre></section>' +
      '<script>window.onload=function(){setTimeout(function(){window.print();},80)};window.onafterprint=function(){window.close()};<\/script>' +
      '</body></html>';
  }

  function unlockAndReload() {
    try { garbaliaAllowNavigation = true; } catch (error) {}
    window.setTimeout(function () { window.location.reload(); }, 450);
  }

  function sendAndPrint(form) {
    const button = form.querySelector('button[type="submit"],button:not([type])');
    const originalText = button ? button.textContent : '';
    const printWindow = window.open('', '_blank', 'width=460,height=760');
    if (!printWindow) {
      alert('ბრაუზერმა ბეჭდვის ფანჯარა დაბლოკა. დაუშვი Pop-ups pos.cours.ge-სთვის.');
      return;
    }

    loadingWindow(printWindow);
    form.dataset.directPrinting = '1';
    if (button) {
      button.disabled = true;
      button.textContent = 'იგზავნება…';
    }

    fetch('/send_order_print.php', {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: {'Accept':'application/json'}
    }).then(function (response) {
      return response.json().catch(function () { return {ok:false,message:'სერვერის პასუხი ვერ დამუშავდა.'}; }).then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.message || 'შეკვეთის გაგზავნა ვერ მოხერხდა.');
        return data;
      });
    }).then(function (data) {
      printWindow.document.open();
      printWindow.document.write(printDocument(data));
      printWindow.document.close();
      unlockAndReload();
    }).catch(function (error) {
      try { printWindow.close(); } catch (closeError) {}
      form.dataset.directPrinting = '0';
      if (button) {
        button.disabled = false;
        button.textContent = originalText;
      }
      alert(error && error.message ? error.message : 'შეკვეთის გაგზავნა ვერ მოხერხდა.');
    });
  }

  function initDirectPrint() {
    document.addEventListener('submit', function (event) {
      const form = event.target;
      const action = form && form.querySelector ? form.querySelector('input[name="action"]') : null;
      if (!action || action.value !== 'send_order') return;
      event.preventDefault();
      event.stopImmediatePropagation();
      if (form.dataset.directPrinting === '1') return;

      const rows = orderInfoRows();
      if (typeof showGarbaliaConfirm === 'function') {
        showGarbaliaConfirm({
          id: 'garbalia-send-order-modal',
          title: 'შეკვეთის გაგზავნა',
          message: 'გაგზავნის შემდეგ ბარისა და სამზარეულოს ქვითრები პირდაპირ გაიხსნება ბეჭდვაზე.',
          info: rows,
          confirmText: 'დიახ, გაგზავნა',
          cancelText: 'არა',
          confirmClass: 'primary',
          onConfirm: function () { sendAndPrint(form); }
        });
      } else {
        sendAndPrint(form);
      }
    }, true);
  }

  function start() {
    addReceiptSettingsNav();
    initDirectPrint();
    window.setTimeout(addReceiptSettingsNav, 300);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
