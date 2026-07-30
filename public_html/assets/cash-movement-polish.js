(function () {
  function money(value) {
    const number = parseFloat(String(value || '').replace(',', '.')) || 0;
    return number.toFixed(2) + ' ₾';
  }

  function movementMeta(value) {
    if (value === 'remove') {
      return {
        title: 'თანხის ამოღების დადასტურება',
        message: 'გადაამოწმე მონაცემები — დადასტურების შემდეგ თანხა სალაროდან გამოაკლდება.',
        action: 'თანხის ამოღება სალაროდან',
        confirm: 'დიახ, ამოღება',
        className: 'danger'
      };
    }
    if (value === 'expense') {
      return {
        title: 'ხარჯის დადასტურება',
        message: 'გადაამოწმე მონაცემები — დადასტურების შემდეგ თანხა ხარჯად ჩაიწერება და სალაროდან გამოაკლდება.',
        action: 'ხარჯის ჩაწერა',
        confirm: 'დიახ, ხარჯად ჩაწერა',
        className: 'danger'
      };
    }
    return {
      title: 'თანხის დამატების დადასტურება',
      message: 'გადაამოწმე მონაცემები — დადასტურების შემდეგ თანხა სალაროს ნაშთს დაემატება.',
      action: 'თანხის დამატება სალაროში',
      confirm: 'დიახ, დამატება',
      className: 'success'
    };
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }

  function injectStyles() {
    if (document.getElementById('garbalia-cash-polish-style')) return;
    const style = document.createElement('style');
    style.id = 'garbalia-cash-polish-style';
    style.textContent = `
      .garbalia-cash-modal{overflow:hidden!important;box-sizing:border-box!important}
      .garbalia-cash-dialog{box-sizing:border-box!important;width:min(520px,calc(100vw - 32px))!important;max-width:calc(100vw - 32px)!important;max-height:calc(100vh - 32px)!important;overflow-y:auto!important;overflow-x:hidden!important;padding:26px!important}
      .garbalia-cash-dialog *{box-sizing:border-box}
      .garbalia-cash-form-holder,.garbalia-cash-popup-form,.garbalia-cash-popup-form label{display:grid!important;min-width:0!important;width:100%!important;max-width:100%!important}
      .garbalia-cash-popup-form{gap:12px!important;overflow:hidden!important}
      .garbalia-cash-popup-form input,.garbalia-cash-popup-form select,.garbalia-cash-popup-form button{display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important}
      .garbalia-cash-popup-form input[type="number"]{appearance:textfield}
      .garbalia-cash-popup-form input[type="number"]::-webkit-inner-spin-button,.garbalia-cash-popup-form input[type="number"]::-webkit-outer-spin-button{margin:0}
      .garbalia-cash-history-dialog{width:min(760px,calc(100vw - 32px))!important;max-width:calc(100vw - 32px)!important}
      .cash-confirm-overlay{position:fixed;inset:0;z-index:10060;display:grid;place-items:center;padding:18px;background:rgba(43,27,16,.54);backdrop-filter:blur(9px);animation:garbaliaFadeIn .16s ease-out}
      .cash-confirm-dialog{position:relative;box-sizing:border-box;width:min(470px,calc(100vw - 32px));max-height:calc(100vh - 32px);overflow-y:auto;overflow-x:hidden;padding:27px;border:1px solid #ead6bd;border-radius:28px;background:linear-gradient(180deg,#fffaf2 0%,#f8ecdd 100%);box-shadow:0 28px 75px rgba(43,27,16,.34);color:#2b1b10;text-align:center;animation:garbaliaPopIn .18s ease-out}
      .cash-confirm-dialog *{box-sizing:border-box}.cash-confirm-logo{display:block;width:48px;height:34px;object-fit:contain;margin:0 auto 10px;mix-blend-mode:multiply}.cash-confirm-close{position:absolute;right:12px;top:12px;width:34px;height:34px;border:0;border-radius:50%;background:rgba(43,27,16,.08);color:#2b1b10;font-size:20px;font-weight:900;cursor:pointer}.cash-confirm-dialog h3{margin:0 0 8px;font-size:1.28rem;font-weight:950}.cash-confirm-dialog>p{max-width:390px;margin:0 auto 16px;color:#6d5140;font-weight:800;line-height:1.45}.cash-confirm-info{display:grid;gap:8px;margin:0 auto 18px}.cash-confirm-row{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:11px 12px;border:1px solid rgba(43,27,16,.09);border-radius:14px;background:rgba(255,255,255,.62);text-align:left}.cash-confirm-row span{color:#7a6657;font-weight:850}.cash-confirm-row strong{max-width:65%;color:#2b1b10;font-weight:950;text-align:right;overflow-wrap:anywhere}.cash-confirm-amount strong{font-size:1.08rem}.cash-confirm-actions{display:grid;grid-template-columns:1fr 1fr;gap:9px}.cash-confirm-actions .btn{width:100%!important;min-height:46px!important;border-radius:14px!important}.cash-confirm-actions .light{background:#f1e2ce!important;color:#2b1b10!important}
      @media(max-width:560px){.garbalia-cash-dialog{width:calc(100vw - 20px)!important;max-width:calc(100vw - 20px)!important;max-height:calc(100vh - 20px)!important;padding:22px 16px 16px!important;border-radius:22px!important}.cash-confirm-overlay{padding:10px}.cash-confirm-dialog{width:calc(100vw - 20px);max-height:calc(100vh - 20px);padding:23px 16px 16px;border-radius:22px}.cash-confirm-actions{grid-template-columns:1fr}.cash-confirm-row{display:block}.cash-confirm-row strong{display:block;max-width:none;margin-top:4px;text-align:left}}
    `;
    document.head.appendChild(style);
  }

  function updateSubmitLabel(form) {
    const type = form.querySelector('select[name="movement_type"]');
    const button = form.querySelector('button[type="submit"],button:not([type])');
    if (!type || !button) return;
    const meta = movementMeta(type.value);
    button.textContent = meta.action;
    button.classList.toggle('danger', type.value !== 'add');
    button.classList.toggle('success', type.value === 'add');
  }

  function showConfirmation(form) {
    const type = form.querySelector('select[name="movement_type"]');
    const amountInput = form.querySelector('input[name="amount"]');
    const noteInput = form.querySelector('input[name="note"]');
    const amount = parseFloat(String(amountInput ? amountInput.value : '').replace(',', '.')) || 0;
    if (amount <= 0) {
      if (amountInput) {
        amountInput.focus();
        amountInput.reportValidity();
      }
      return;
    }

    const meta = movementMeta(type ? type.value : 'add');
    const note = noteInput && noteInput.value.trim() ? noteInput.value.trim() : 'კომენტარი არ არის';
    const old = document.getElementById('cash-movement-confirmation');
    if (old) old.remove();

    const overlay = document.createElement('div');
    overlay.id = 'cash-movement-confirmation';
    overlay.className = 'cash-confirm-overlay';
    overlay.innerHTML = '<div class="cash-confirm-dialog" role="dialog" aria-modal="true">' +
      '<button type="button" class="cash-confirm-close" aria-label="დახურვა">×</button>' +
      '<img class="cash-confirm-logo" src="/Logo.png?v=12" alt="GARBALIA">' +
      '<h3>' + escapeHtml(meta.title) + '</h3>' +
      '<p>' + escapeHtml(meta.message) + '</p>' +
      '<div class="cash-confirm-info">' +
        '<div class="cash-confirm-row"><span>მოქმედება</span><strong>' + escapeHtml(meta.action) + '</strong></div>' +
        '<div class="cash-confirm-row cash-confirm-amount"><span>თანხა</span><strong>' + escapeHtml(money(amount)) + '</strong></div>' +
        '<div class="cash-confirm-row"><span>კომენტარი</span><strong>' + escapeHtml(note) + '</strong></div>' +
      '</div>' +
      '<div class="cash-confirm-actions"><button type="button" class="btn light" data-cash-confirm-cancel>უკან დაბრუნება</button><button type="button" class="btn ' + meta.className + '" data-cash-confirm-submit>' + escapeHtml(meta.confirm) + '</button></div>' +
      '</div>';
    document.body.appendChild(overlay);

    const close = function () { overlay.remove(); };
    overlay.querySelector('.cash-confirm-close').addEventListener('click', close);
    overlay.querySelector('[data-cash-confirm-cancel]').addEventListener('click', close);
    overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); });

    const esc = function (event) {
      if (event.key === 'Escape') {
        close();
        document.removeEventListener('keydown', esc);
      }
    };
    document.addEventListener('keydown', esc);

    overlay.querySelector('[data-cash-confirm-submit]').addEventListener('click', function () {
      form.dataset.cashMovementConfirmed = '1';
      const submitButton = form.querySelector('button[type="submit"],button:not([type])');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'ინახება…';
      }
      close();
      try { garbaliaAllowNavigation = true; } catch (error) {}
      form.submit();
    });
  }

  function init() {
    injectStyles();

    document.addEventListener('change', function (event) {
      if (event.target && event.target.matches('select[name="movement_type"]')) {
        const form = event.target.closest('form');
        if (form) updateSubmitLabel(form);
      }
    });

    document.addEventListener('submit', function (event) {
      const form = event.target;
      const action = form && form.querySelector ? form.querySelector('input[name="action"]') : null;
      if (!action || action.value !== 'cash_movement') return;
      if (form.dataset.cashMovementConfirmed === '1') return;
      event.preventDefault();
      event.stopImmediatePropagation();
      showConfirmation(form);
    }, true);

    window.setTimeout(function () {
      document.querySelectorAll('form').forEach(function (form) {
        const action = form.querySelector('input[name="action"][value="cash_movement"]');
        if (action) updateSubmitLabel(form);
      });
    }, 150);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
