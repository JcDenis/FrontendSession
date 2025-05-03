/*global $, dotclear, jsToolBar */
'use strict';

$(() => {
  // Get plugin preferences data
  const fs = dotclear.getData('FrontendSession');
  dotclear.fs = fs;

  if (typeof jsToolBar === 'function') {
    $('#FrontendSessionconnected').each(function () {
      const FrontendSessionJsToolBar = new jsToolBar(this);
      FrontendSessionJsToolBar.draw('xhtml');
    });
  }
  // Condition page URL selector helper
  const FrontendSessionUrlSelector = document.getElementById('condition_page_selector');
  FrontendSessionUrlSelector?.addEventListener('click', (e) => {
    window.open(
      dotclear.fs.popup_posts,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
    );
    e.preventDefault();
    return false;
  });
});
