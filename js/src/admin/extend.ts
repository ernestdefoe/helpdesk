import app from 'flarum/admin/app';
import Admin from 'flarum/common/extenders/Admin';

/**
 * Help Desk's settings page. Every control has a reader, named beside it.
 *
 * 🚨 Exported from js/src/admin/index.ts and nowhere else: this imports
 * `flarum/admin/app`, which does not exist on the forum.
 */
const K = (key: string) => `ernestdefoe-helpdesk.${key}`;
const t = (key: string) => app.translator.trans(`ernestdefoe-helpdesk.admin.${key}`);

const field =
  (key: string, type: string, extra: Record<string, unknown> = {}) =>
  () => ({
    setting: K(key),
    label: t(`${key}_label`),
    help: t(`${key}_help`),
    type,
    ...extra,
  });

export default [
  new Admin()
    // less/lib/tokens.less (@helpdesk-accent, @helpdesk-bar); empty keeps the theme's own colours
    .setting(field('accent', 'color-preview'), 90)
    .setting(field('bar', 'color-preview'), 89)
    // src/Content/Page.php's font stamp and less/lib/fonts.less
    .setting(field('fonts', 'boolean'), 88)
    // components/Hero.tsx
    .setting(field('hero_title', 'text'), 80)
    .setting(field('hero_text', 'text'), 79)
    .setting(field('popular', 'text'), 78)
    // index.tsx and components/TopicCards.tsx
    .setting(field('topics', 'boolean'), 70)
    .setting(() => ({ setting: K('topic_count'), label: t('topic_count_label'), type: 'number', min: 2, max: 24 }), 69),
];
