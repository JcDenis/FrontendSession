/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  dotclear.FrontendSession = dotclear.getData('FrontendSession');

  dotclear.FrontendSession.pendingCount = (icon) => {
    dotclear.services(
      'FrontendSessionPendingCount',
      (data) => {
        try {
          const response = JSON.parse(data);
          if (response?.success) {
            if (response?.payload.ret) {
              const { msg } = response.payload;
              if (msg !== undefined && msg !== dotclear.FrontendSession.counter) {
                const href = icon.attr('href');
                const param = `${href.includes('?') ? '&' : '?'}status=${dotclear.FrontendSession.status}`;
                const url = `${href}${param}`;
                // First pass or counter changed
                const link = $(`#dashboard-main #icons p a[href^="${url}"]`);
                if (link.length) {
                  // Update count if exists
                  const nb_label = icon.children('span.FrontendSession-icon-title-pending');
                  if (nb_label.length) {
                    nb_label.text(msg);
                  }
                } else if (msg !== '' && icon.length) {
                  // Add full element (link + counter)
                  const xml = ` <a href="${url}"><span class="FrontendSession-icon-title-pending">${msg}</span></a>`;
                  icon.after(xml);
                }
                const { nb } = response.payload;
                // Badge on icon
                dotclear.badge(icon, {
                  id: 'FrontendSession_pending',
                  value: nb,
                  remove: nb <= 0,
                  type: 'info',
                  sibling: true,
                  icon: true,
                });
                // Badge on module
                dotclear.badge($('#incoming-submissions'), {
                  id: 'FrontendSession_pending',
                  value: nb,
                  remove: nb <= 0,
                  type: 'info',
                });
                // Store current counter
                dotclear.FrontendSession.counter = msg;
              }
            }
          } else {
            console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
            return;
          }
        } catch (e) {
          console.log(e);
        }
      },
      (error) => {
        console.log(error);
      },
      true, // Use GET method
      { json: 1 },
    );
  };

  let icon = $('#dashboard-main #icons p a[href*="Users"]');
  if (!icon.length) {
    icon = $('#dashboard-main #icons p #icon-process-users-fav');
  }
  if (icon.length) {
    // Icon exists on dashboard
    // First pass
    dotclear.FrontendSession.pendingCount(icon);
    // Then fired every 60 seconds
    dotclear.FrontendSession.timer = setInterval(
      dotclear.FrontendSession.pendingCount,
      (dotclear.FrontendSession.interval || 60) * 1000,
      icon,
    );
  }
});
