<?php
header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
readfile(__DIR__ . '/style.css');
?>

/* GARBALIA runtime popup refinements */
.garbalia-confirm-dialog{
  width:min(470px,calc(100vw - 40px))!important;
  max-height:calc(100vh - 42px)!important;
  overflow-y:auto!important;
  overflow-x:hidden!important;
  padding-bottom:22px!important;
}
.garbalia-confirm-info{
  max-height:min(48vh,420px)!important;
  overflow-y:auto!important;
  overflow-x:hidden!important;
  padding-right:2px!important;
}
.garbalia-confirm-info div{
  min-width:0!important;
}
.garbalia-confirm-info span,
.garbalia-confirm-info strong{
  min-width:0!important;
  word-break:break-word!important;
  overflow-wrap:anywhere!important;
}
.garbalia-confirm-info strong{
  white-space:normal!important;
  text-align:right!important;
}
.garbalia-confirm-actions{
  position:sticky!important;
  bottom:0!important;
  z-index:2!important;
  margin-top:12px!important;
  padding-top:10px!important;
  background:linear-gradient(180deg,rgba(248,236,221,0),#f8ecdd 32%,#f8ecdd 100%)!important;
}
@media(max-width:560px){
  .garbalia-confirm-dialog{width:calc(100vw - 24px)!important;max-height:calc(100vh - 24px)!important;padding:24px 18px 18px!important}
  .garbalia-confirm-info{max-height:50vh!important}
}
