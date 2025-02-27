/*global $, dotclear, jsToolBar */
'use strict';

$(() => {
  if (typeof jsToolBar === 'function') {
    $('#FrontendSessionconnected').each(function () {
      const FrontendSessionJsToolBar = new jsToolBar(this);
      FrontendSessionJsToolBar.draw('xhtml');
    });
  }
});
