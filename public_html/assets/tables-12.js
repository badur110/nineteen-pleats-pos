(function () {
  'use strict';

  function injectSixColumnLayout() {
    if (document.getElementById('garbalia-twelve-tables-style')) return;

    const style = document.createElement('style');
    style.id = 'garbalia-twelve-tables-style';
    style.textContent = `
      @media (min-width:1250px) {
        html body.app-shell:has(.tables-grid) main.wrap .tables-grid {
          grid-template-columns:repeat(6,minmax(0,1fr))!important;
          gap:14px!important;
        }
        html body.app-shell:has(.tables-grid) main.wrap .table-card {
          min-height:clamp(122px,13.5vh,154px)!important;
          padding-left:13px!important;
          padding-right:13px!important;
        }
      }
      @media (min-width:981px) and (max-width:1249px) {
        html body.app-shell:has(.tables-grid) main.wrap .tables-grid {
          grid-template-columns:repeat(4,minmax(0,1fr))!important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  async function synchronizeTables() {
    const grid = document.querySelector('.tables-grid');
    if (!grid) return;

    try {
      const response = await fetch('/ensure-tables.php', {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      if (!response.ok) return;

      const result = await response.json();
      if (!result || result.ok !== true || result.changed !== true) return;

      const reloadKey = 'garbalia-tables-12-reloaded';
      if (sessionStorage.getItem(reloadKey) === '1') return;
      sessionStorage.setItem(reloadKey, '1');
      window.location.reload();
    } catch (error) {
      // Existing tables remain fully usable if synchronization is temporarily unavailable.
    }
  }

  function start() {
    injectSixColumnLayout();
    synchronizeTables();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
