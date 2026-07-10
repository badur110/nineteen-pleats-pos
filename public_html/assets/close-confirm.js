document.addEventListener('submit', function (event) {
  const form = event.target;
  const action = form && form.querySelector ? form.querySelector('input[name="action"]') : null;
  if (!action || action.value !== 'close_order') return;
  if (form.dataset.garbaliaConfirmedClose === '1') return;

  event.preventDefault();
  event.stopPropagation();

  const items = Array.from(document.querySelectorAll('.order-item:not(.cancelled)'));
  const totalText = document.querySelector('.total-box') ? document.querySelector('.total-box').textContent.trim() : '0.00 ₾';
  const paymentSelect = form.querySelector('select[name="payment_type"]');
  const paymentText = paymentSelect ? paymentSelect.options[paymentSelect.selectedIndex].text.trim() : '—';
  const tableTitle = document.querySelector('.page-head h1') ? document.querySelector('.page-head h1').textContent.trim() : 'მაგიდა';

  const info = [];
  info.push({label: 'მაგიდა', value: tableTitle});
  info.push({label: 'გადახდა', value: paymentText});
  info.push({label: 'საბოლოო ჯამი', value: totalText, className: 'diff-zero'});

  items.slice(0, 8).forEach(function (item, index) {
    const name = item.querySelector('strong') ? item.querySelector('strong').textContent.trim() : ('პროდუქტი #' + (index + 1));
    const sumLine = item.querySelector('small') ? item.querySelector('small').textContent.trim().replace(/\s+/g, ' ') : '';
    const cleanSum = sumLine.replace(/^.*ჯამი:\s*/u, '').trim();
    info.push({
      label: 'პროდუქტი ' + (index + 1),
      value: cleanSum ? (name + ' — ' + cleanSum) : name
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
