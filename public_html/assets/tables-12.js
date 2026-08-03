(function () {
  'use strict';

  function injectLayoutStyles() {
    if (document.getElementById('garbalia-tables-layout-style')) return;

    const style = document.createElement('style');
    style.id = 'garbalia-tables-layout-style';
    style.textContent = `
      .garbalia-takeaway-section{
        width:min(1380px,100%);
        margin:0 auto 16px;
        padding:14px;
        border:1px solid rgba(43,27,16,.10);
        border-radius:22px;
        background:linear-gradient(145deg,rgba(255,253,248,.96),rgba(242,226,205,.92));
        box-shadow:0 14px 32px rgba(43,27,16,.08),inset 0 1px 0 rgba(255,255,255,.86);
      }
      .garbalia-takeaway-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin-bottom:10px;
      }
      .garbalia-takeaway-head strong{
        display:block;
        font-size:.94rem;
        font-weight:950;
        color:#2b1b10;
      }
      .garbalia-takeaway-head span{
        color:#7a6657;
        font-size:.74rem;
        font-weight:800;
      }
      .garbalia-takeaway-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(190px,250px));
        justify-content:center;
        gap:12px;
      }
      body.app-shell .garbalia-takeaway-grid .table-card{
        min-height:92px!important;
        padding:13px 16px!important;
        border-radius:19px!important;
        border-color:#b9782d!important;
        background:
          radial-gradient(circle at 90% -20%,rgba(255,255,255,.72),transparent 44%),
          linear-gradient(145deg,#fff7df,#f2d69d)!important;
        box-shadow:0 12px 28px rgba(120,72,26,.13),inset 0 1px 0 rgba(255,255,255,.92)!important;
      }
      body.app-shell .garbalia-takeaway-grid .table-card.occupied{
        border-color:#b5473d!important;
        background:linear-gradient(145deg,#fff6ef,#ffd9cd)!important;
      }
      body.app-shell .garbalia-takeaway-grid .table-card.pending{
        border-color:#d28c20!important;
        background:linear-gradient(145deg,#fff9e8,#ffe0a3)!important;
      }
      body.app-shell .garbalia-takeaway-grid .table-card span:before{
        content:"↗";
        display:inline-grid;
        place-items:center;
        width:26px;
        height:26px;
        margin-right:8px;
        border-radius:9px;
        background:rgba(43,27,16,.10);
        font-size:.82rem;
        vertical-align:middle;
      }
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
      @media (max-width:640px) {
        .garbalia-takeaway-section{padding:11px;border-radius:18px}
        .garbalia-takeaway-head{align-items:flex-start;flex-direction:column;gap:2px}
        .garbalia-takeaway-grid{grid-template-columns:1fr 1fr;gap:8px}
        body.app-shell .garbalia-takeaway-grid .table-card{min-height:88px!important;padding:11px 9px!important}
      }
      @media (max-width:390px) {
        .garbalia-takeaway-grid{grid-template-columns:1fr}
      }
    `;
    document.head.appendChild(style);
  }

  function organizeTakeawayCards() {
    const grid = document.querySelector('.tables-grid');
    if (!grid) return;

    const takeawayCards = Array.from(grid.querySelectorAll('.table-card')).filter(function (card) {
      const name = card.querySelector('span');
      return name && /^გატანა\s*[12]$/i.test(name.textContent.trim());
    });

    if (!takeawayCards.length) return;

    let section = document.querySelector('.garbalia-takeaway-section');
    if (!section) {
      section = document.createElement('section');
      section.className = 'garbalia-takeaway-section';
      section.innerHTML = '<div class="garbalia-takeaway-head"><div><strong>გატანის შეკვეთები</strong><span>წასაღები შეკვეთებისთვის ცალკე ორი ადგილი</span></div><span>2 გატანა</span></div><div class="garbalia-takeaway-grid"></div>';
      grid.parentNode.insertBefore(section, grid);
    }

    const takeawayGrid = section.querySelector('.garbalia-takeaway-grid');
    takeawayCards.forEach(function (card) {
      card.classList.add('takeaway-card');
      takeawayGrid.appendChild(card);
    });
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

      const reloadKey = 'garbalia-tables-and-takeaway-v2';
      if (sessionStorage.getItem(reloadKey) === '1') return;
      sessionStorage.setItem(reloadKey, '1');
      window.location.reload();
    } catch (error) {
      // Existing tables remain fully usable if synchronization is temporarily unavailable.
    }
  }

  function start() {
    injectLayoutStyles();
    organizeTakeawayCards();
    synchronizeTables();
    window.setTimeout(organizeTakeawayCards, 250);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
