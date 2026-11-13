import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import extractText from 'flarum/common/utils/extractText';
import { data, fmt, t, topics as forumTopics } from '../util';
import { openSearch } from '../search';

/**
 * The search hero at the top of the home page.
 *
 * The box is a real input, and pressing Enter (or a popular chip) opens
 * core's own search with the words already in it: discussions, posts,
 * members and any source another extension adds (Knowledge Base's
 * articles, say), searched by whatever driver the forum runs (core's,
 * Typesense, Manticore, OpenSearch). Help Desk never searches anything
 * itself.
 */
export default class Hero extends Component {
  value = '';

  oninit(vnode: any) {
    super.oninit(vnode);

    this.value = app.search.state.getValue() || '';
  }

  view() {
    const title = app.forum.attribute<string>('helpdeskHeroTitle') || t('hero.title');
    const popular = app.forum.attribute<string[]>('helpdeskPopular') || [];
    const submit = (q: string) => openSearch(q);

    return (
      <header className="HelpDeskHero Hero">
        <div className="container">
          <h1 className="HelpDeskHero-title">{title}</h1>
          <p className="HelpDeskHero-text">{this.text()}</p>
          <form
            className="HelpDeskHero-search"
            role="search"
            onsubmit={(e: Event) => {
              e.preventDefault();
              submit(this.value);
            }}
          >
            <button type="submit" className="HelpDeskHero-go" aria-label={extractText(t('hero.label'))}>
              <Icon name="fas fa-search" />
            </button>
            <input
              id="helpdesk-search"
              className="HelpDeskHero-input"
              type="search"
              enterkeyhint="search"
              autocomplete="off"
              aria-label={extractText(t('hero.label'))}
              placeholder={extractText(t('hero.placeholder'))}
              value={this.value}
              oninput={(e: InputEvent) => {
                this.value = (e.target as HTMLInputElement).value;
              }}
            />
            <kbd className="HelpDeskHero-key" title={extractText(t('hero.shortcut'))} aria-hidden="true">
              /
            </kbd>
          </form>
          {popular.length > 0 && (
            <div className="HelpDeskHero-popular">
              <span className="HelpDeskHero-popularLabel">{t('hero.popular')}</span>
              {popular.map((q) => (
                <button type="button" className="HelpDeskHero-chip" onclick={() => submit(q)}>
                  {q}
                </button>
              ))}
            </div>
          )}
        </div>
      </header>
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
