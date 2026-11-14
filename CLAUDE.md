# FrontendSession

Notes for AI assistants working on the Dotclear plugin FrontendSession.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Allow session on frontend.

No admin page. Public side: widgets.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/CommentOptions.php`: comment options for frontend pages using session.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendBehaviors.php`: public behaviors.
* `src/FrontendSession.php`: module session helper.
* `src/FrontendSessionProfil.php`: module template connected page helper.
* `src/FrontendTemplate.php`: template tags.
* `src/FrontendUrl.php`: public URL handler.
* `src/Install.php`: install and update, default settings.
* `src/Mail.php`: module mail helper.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/PluginTelegramBehaviors.php`: behaviors for the plugin Telegram.
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/RecordExtendPost.php`: daRepo module record extension.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/Widgets.php`: widgets definition and rendering.
* `js/`: `backend-blogpref.js`, `backend-dashboard.js`, `frontend-session.js`.
* `default-templates/`: public templates.
* Widgets: `FrontendSession`.
* Behaviors used: `initWidgets`, `adminDashboardHeaders`, `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `adminBlogPreferencesHeaders`, `adminPopupPosts`, `adminPostEditorTags`, `adminSimpleMenuAddType`, `adminSimpleMenuBeforeEditV2`, `adminUsersActions`, `adminBeforeUserUpdate`, `adminBeforeUserEnable`, `coreBlogGetPosts`, `publicBeforeCommentCreate`, `publicHeadContent`, `publicEntryAfterContent`, `publicCommentAfterContent`, `publicCommentFormBeforeContent`.

## Pitfalls

* Other modules rely on its public sessions (AboutTheAuthor2, Discussion, FrontendWebauthn, Oauth2Connect, ReadingTracking, RestrictedReading): a change of its API or behaviors must keep them working.

## Testing

* Check the widgets (admin and public), in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
