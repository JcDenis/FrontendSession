/*global dotclear */
'use strict';

dotclear.ready(() => {
  const fs = dotclear.getData('frontend_session');
  if (fs?.connected !== true) {
    return;
  }
  document.querySelector('#c_name')?.setAttribute('value', fs.name ?? '');
  document.querySelector('#c_mail')?.setAttribute('value', fs.email ?? '');
  document.querySelector('#c_site')?.setAttribute('value', fs.site ?? '');
});
