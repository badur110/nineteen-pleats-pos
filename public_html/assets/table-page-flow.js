(function () {
  'use strict';

  function applyNaturalTableFlow() {
    if (document.getElementById('garbalia-table-natural-flow')) return;
    const tablePage = document.querySelector('body.page-table, body.app-shell.page-table');
    if (!tablePage) return;

    const style = document.createElement('style');
    style.id = 'garbalia-table-natural-flow';
    style.textContent = `
      html body.app-shell.page-table .pos-grid>.card:first-child{
        max-height:none!important;
        min-height:0!important;
        height:auto!important;
        overflow:visible!important;
        scrollbar-width:auto!important;
      }
      html body.app-shell.page-table .pos-grid>.card:first-child::-webkit-scrollbar{
        display:none!important;
      }
      html body.app-shell.page-table .category-title{
        position:static!important;
        top:auto!important;
      }
      html body.app-shell.page-table main.wrap{
        overflow:visible!important;
      }
      html body.app-shell.page-table .pos-grid{
        align-items:start!important;
      }
      @media(min-width:981px){
        html body.app-shell.page-table .current-order-card{
          position:sticky!important;
          top:74px!important;
          max-height:calc(100dvh - 90px)!important;
          overflow-y:auto!important;
          overflow-x:hidden!important;
        }
      }
      @media(max-width:980px){
        html body.app-shell.page-table .current-order-card{
          position:static!important;
          max-height:none!important;
          overflow:visible!important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyNaturalTableFlow);
  } else {
    applyNaturalTableFlow();
  }
})();
