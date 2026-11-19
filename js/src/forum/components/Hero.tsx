import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import extractText from 'flarum/common/utils/extractText';
import { data, fmt, t, topics as forumTopics } from '../util';
import HeroSearch from './HeroSearch';

/**
 * The search hero at the top of the home page.
 *
 * The box is core's own search (HeroSearch): discussions, posts, members and
 * any source another extension adds, searched by whatever driver the forum
 * runs (core's, Typesense, Manticore, OpenSearch). A popular chip opens
 * core's search results page. Help Desk never searches anything itself.
 */
export default class Hero extends Component {
  view() {
    const title = app.forum.attribute<string>('helpdeskHeroTitle') || t('hero.title');

    return (
      <header className="HelpDeskHero Hero">
        <div className="container">
          <h1 className="HelpDeskHero-title">{title}</h1>
          <p className="HelpDeskHero-text">{this.text()}</p>
          {this.searchForm()}
          {this.popularView()}
        </div>
      </header>
    );
  }

  /** Core's search box, with the "/" shortcut beside it. */
  searchForm() {
    return (
      <div className="HelpDeskHero-search">
        <HeroSearch state={app.search.state} />
        <kbd className="HelpDeskHero-key" title={extractText(t('hero.shortcut'))} aria-hidden="true">
          /
        </kbd>
      </div>
    );
  }

  /** Popular searches as chips, when the admin set some. */
  popularView() {
    const popular = app.forum.attribute<string[]>('helpdeskPopular') || [];
    if (!popular.length) return null;

    return (
      <div className="HelpDeskHero-popular">
        <span className="HelpDeskHero-popularLabel">{t('hero.popular')}</span>
        {popular.map((q) => (
          <button type="button" className="HelpDeskHero-chip" onclick={() => m.route.set(app.route('index', { q }))}>
            {q}
          </button>
        ))}
      </div>
    );
  }

  text() {
    const custom = app.forum.attribute<string>('helpdeskHeroText');
    if (custom) return custom;

    const { mode, topics, guides } = data();
    // Top-level topics only: a question in a child tag is in its parent too.
    const answered = mode === 'none' ? 0 : forumTopics().reduce((sum, tag) => sum + (topics[tag.id()]?.[1] || 0), 0);
    const parts: string[] = [];

    if (answered) parts.push(extractText(t('hero.answered', { count: answered, formatted: fmt(answered) })));
    if (guides) parts.push(extractText(t('hero.guides', { count: guides })));

    if (!parts.length) return t('hero.text_plain');

    return t('hero.text', { things: parts.length === 2 ? extractText(t('hero.both', { first: parts[0], second: parts[1] })) : parts[0] });
  }
}
