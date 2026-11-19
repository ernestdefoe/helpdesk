import GlobalSearch from 'flarum/forum/components/GlobalSearch';
import type { SearchAttrs } from 'flarum/forum/components/GlobalSearch';
import extractText from 'flarum/common/utils/extractText';
import { t } from '../util';

/**
 * Core's own search box, as the header uses it, with the hero's wording. It
 * opens core's search with every source (another extension's included),
 * searched by whatever driver the forum runs; Help Desk searches nothing
 * itself.
 */
export default class HeroSearch extends GlobalSearch {
  static initAttrs(attrs: SearchAttrs) {
    super.initAttrs(attrs);
    attrs.label = extractText(t('hero.placeholder'));
  }
}
