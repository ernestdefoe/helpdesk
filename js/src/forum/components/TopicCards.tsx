import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import { data, t, topics } from '../util';

/**
 * "Browse by topic": one card per top-level primary tag, with how many
 * questions it holds and how many of them are answered.
 *
 * The numbers are the ones extend.php put in the page (one cached grouped
 * query for the whole forum). A topic with no questions, or a forum where
 * nothing keeps an answered state, shows its plain discussion count.
 */
export default class TopicCards extends Component<{ heading?: boolean }> {
  view() {
    const all = topics();
    if (!all.length) return null;

    const limit = app.forum.attribute<number>('helpdeskTopicCount') || 6;
    const shown = all.slice(0, limit);

    return (
      <section className="HelpDeskTopics" aria-labelledby={this.attrs.heading === false ? undefined : 'helpdesk-topics'}>
        {this.attrs.heading !== false && (
          <h2 className="HelpDeskSection-title" id="helpdesk-topics">
            {t('topics.heading')}
          </h2>
        )}
        <ul className="HelpDeskTopics-grid">
          {shown.map((tag) => (
            <li key={tag.id()}>{this.card(tag)}</li>
          ))}
        </ul>
        {/* On phones only four cards show (CSS), so the link is there whenever there are more. */}
        <Link className={'HelpDeskTopics-all' + (all.length > shown.length ? ' is-more' : '')} href={app.route('tags')}>
          {t('topics.all', { count: all.length })}
        </Link>
      </section>
    );
  }

  card(tag: any) {
    const numbers = data().topics[tag.id()];
    const [questions, answered] = numbers || [0, 0];
    const percent = questions ? Math.round((answered / questions) * 100) : 0;
    const count = questions ? t('topics.questions', { count: questions }) : t('topics.discussions', { count: tag.discussionCount() || 0 });

    return (
      <Link
        className="HelpDeskTopic"
        href={app.route('tag', { tags: tag.slug() })}
        style={tag.color() ? { '--topic-color': tag.color() } : undefined}
      >
        <span className="HelpDeskTopic-icon" aria-hidden="true">
          <Icon name={tag.icon() || 'far fa-folder'} />
        </span>
        <span className="HelpDeskTopic-body">
          <span className="HelpDeskTopic-name">{tag.name()}</span>
          {tag.description() && <span className="HelpDeskTopic-text">{tag.description()}</span>}
          <span className="HelpDeskTopic-meta">
            {count}
            {questions > 0 && (
              <>
                {' · '}
                <span className={'HelpDeskTopic-answered' + (percent >= 90 ? ' is-high' : '')}>{t('topics.answered', { percent })}</span>
              </>
            )}
          </span>
        </span>
      </Link>
    );
  }
}
