# Help Desk: working rules

- **Commit straight to `main` and push. Never open a pull request on this repo.** Commit subjects `feat:` / `fix:` (the release drafter bumps from the subject).
- `js/dist` is committed and is what forums load. After any change under `js/src`: `npm ci` then `npm run build` in `js/`, `npx prettier --check src`, and commit the bundle; CI fails if it is stale.
- Releasing is Ernest's click. Nothing is announced until he approves the wording. Every change goes to the Command Center session for review before release.

## Design rules that exist for a reason

- **Help Desk never decides "answered".** ernestdefoe/answers (state column) or fof/best-answer (best post) does; `Answered\*`, bound by `Extend\Conditional`, says which. With neither, the answered UI is not drawn.
- **Topic numbers = one grouped query for the whole forum**, cached 10 minutes (`Topics::all`), then cut to the tags the visitor can see (one query) before they go in the page payload. Never per tag, never uncut.
- Server work in `Content\Page` runs only for visitors on Help Desk (`ActiveTheme`; `WardrobeActiveTheme` when Wardrobe is on). Wardrobe serves each theme its own JS bundle, so the JS doesn't check.
- The question page's side column is fields of the discussion (`Api/DiscussionFields`: helpdeskYes, helpdeskHelpful written with PATCH /api/discussions/{id} like flarum/subscriptions, helpdeskGuides), visible only on the discussion's own Show/Update and only for Help Desk visitors. No custom controllers.
- Every optional extension is wired with `Extend\Conditional` + `Extend\Bind` (Answered\*, Guides\*, TopicNumbers, WardrobeActiveTheme). No `isEnabled()`/`class_exists()` in PHP. KB classes stay strings (it is not a Composer dependency); results go through KB's own `visible` scope.
- Build for Floxum 100: run `pre-floxum` before every release; list every integration in `optional-dependencies` AND `suggest`.
- Keep Flarum's page structure: the topic tree is an item in core's `PageStructure` container, the side column is core's discussion sidebar. Search is core's modal (so any search driver applies).
- LESS overrides core's colour variables (tokens.less) so every core/extension component follows the theme; mode-dependent colours live in `[data-theme^=light|dark]`, never `prefers-color-scheme`. Selectors that must beat theme/extension link rules carry `html:not(#hd)`.
- extend.php uses string literals outside closures, never class constants.

## Testing

- `DB_DRIVER=mysql DB_PREFIX=dev_ … composer test:setup` whenever the driver changes (check `vendor/flarum/testing/src/integration/tmp/config.php`), then `composer test`. CI also runs PostgreSQL 18.
- Screenshots + overflow sweep: `~/github/scopes/helpdesk/shots.mjs` (`sweep` for 390–1920). Seed: `~/github/scopes/helpdesk/seed.php`. Local forum `~/Herd/helpdeskdev` (prefix `hd_`).
