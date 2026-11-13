import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import DiscussionHero from 'flarum/forum/components/DiscussionHero';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import PageStructure from 'flarum/forum/components/PageStructure';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import humanTime from 'flarum/common/helpers/humanTime';
import username from 'flarum/common/helpers/username';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import type ItemList from 'flarum/common/utils/ItemList';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
import Hero from './components/Hero';
import TopicCards from './components/TopicCards';
import TopicTree from './components/TopicTree';
import { Contents, Guides, Solved } from './components/QuestionAside';
import registerWidgets from './widgets';
import { slashToSearch } from './search';
import { answerCount, data, fmt, has, isQuestion, stateOf, t, topics } from './util';

export { default as HelpDeskHero } from './components/Hero';
export { default as HelpDeskTopicCards } from './components/TopicCards';

/** The plain home page: not a tag, not Following, not a search. */
const onHome = () => app.current.get('routeName') === 'index' && !app.search.state.params().q;

/**
 * Help Desk's JS runs only for visitors on Help Desk: Wardrobe serves each
 * theme its own bundle, and a forum without Wardrobe has only this theme.
 * So nothing below asks which theme is active.
 */
app.initializers.add('ernestdefoe-helpdesk', () => {
  slashToSearch();
  registerWidgets();

  /* ---------- The home page ---------- */

  override(IndexPage.prototype, 'hero', function (original: () => Mithril.Children) {
    return app.current.get('routeName') === 'index' ? <Hero /> : original();
  });

  extend(IndexPage.prototype, 'contentItems', function (items: ItemList<Mithril.Children>) {
    if (onHome() && app.forum.attribute('helpdeskTopics') && topics().length) {
      items.add('helpdeskTopics', <TopicCards />, 110);
    }
  });

  extend(IndexPage.prototype, 'toolbarItems', function (items: ItemList<Mithril.Children>) {
    if (onHome()) {
      items.add('helpdeskHeading', <h2 className="HelpDeskSection-title HelpDeskList-heading">{t('list.heading')}</h2>, 200);
    }
  });

  // "Start a discussion" is "Ask a question" here; core's own button and action.
  extend(IndexSidebar.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    const button: any = items.has('newDiscussion') && items.get('newDiscussion');
    if (button && (app.forum.attribute('canStartDiscussion') || !app.session.user)) {
      button.children = [t('ask_button')];
      if (button.attrs) button.attrs['aria-label'] = extractText(t('ask_button'));
    }
  });

  /* ---------- The question list ---------- */

  // The answer box at the left of each row. On an Answers question it is
  // Answers' own count (styled); elsewhere the reply count, green once a
  // best answer is picked.
  extend(DiscussionListItem.prototype, 'statsItems', function (this: DiscussionListItem, items: ItemList<Mithril.Children>) {
    const discussion = this.attrs.discussion;
    if (isQuestion(discussion) || !items.has('replyCount')) return;

    const count = answerCount(discussion);
    const state = stateOf(discussion);

    items.setContent(
      'replyCount',
      <span className={classList('HelpDeskCount', count > 0 && 'HelpDeskCount--has', state === 'solved' && 'HelpDeskCount--solved')}>
        <b>{fmt(count)}</b>
        <span>{t('list.replies', { count })}</span>
      </span>
    );
  });

  extend(DiscussionListItem.prototype, 'infoItems', function (this: DiscussionListItem, items: ItemList<Mithril.Children>) {
    const discussion = this.attrs.discussion;
    const user = discussion.user();

    if (items.has('terminalPost')) items.remove('terminalPost');

    items.add('helpdeskAsked', <span className="HelpDeskAsked">{t('list.asked_by', { username: username(user) })}</span>, -10);
    items.add('helpdeskWhen', <span className="HelpDeskWhen">{humanTime((discussion.lastPostedAt() || discussion.createdAt())!)}</span>, -20);
  });

  extend(DiscussionListItem.prototype, 'elementAttrs', function (this: DiscussionListItem, attrs: any) {
    const state = stateOf(this.attrs.discussion);
    if (state) attrs.className = classList(attrs.className, 'HelpDesk--' + state);
  });

  /* ---------- A question ---------- */

  // The breadcrumb takes the place of the tag labels; the facts sit under the title.
  extend(DiscussionHero.prototype, 'items', function (this: DiscussionHero, items: ItemList<Mithril.Children>) {
    const discussion = this.attrs.discussion as Discussion;

    // ernestdefoe/waymark draws the forum's own breadcrumb above the page:
    // Help Desk's would say it twice.
    if (has('flarum-tags') && items.has('tags')) {
      items.remove('tags');
      if (!has('ernestdefoe-waymark')) items.add('helpdeskCrumbs', crumbs(discussion), 20);
    }

    items.add('helpdeskFacts', facts(discussion), -10);
  });

  // The topic tree, as a column of the page's own container.
  extend(PageStructure.prototype, 'containerItems', function (this: PageStructure, items: ItemList<Mithril.Children>) {
    const discussion = app.current.get('discussion') as Discussion | undefined;

    if (this.attrs.className === 'DiscussionPage' && discussion && !this.attrs.loading && topics().length) {
      items.add('helpdeskTree', <div className="HelpDeskPage-tree">{<TopicTree discussion={discussion} />}</div>, 110);
    }

    // On phones core's sidebar sits ABOVE the question, so the solved box and
    // the guides are drawn again after it (CSS shows one copy per width; both
    // read the same cached request).
    if (this.attrs.className === 'DiscussionPage' && discussion && !this.attrs.loading) {
      items.add(
        'helpdeskAfter',
        <div className="HelpDeskPage-after">
          <Solved discussion={discussion} />
          <Guides discussion={discussion} />
        </div>,
        0
      );
    }
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function (this: DiscussionPage, items: ItemList<Mithril.Children>) {
    const discussion = (this as any).discussion as Discussion | undefined;
    if (!discussion) return;

    items.add('helpdeskContents', <Contents discussion={discussion} />, 50);
    items.add('helpdeskSolved', <Solved discussion={discussion} />, 40);
    items.add('helpdeskGuides', <Guides discussion={discussion} />, 30);
  });
});

/** Community / Topic / Sub-topic. */
function crumbs(discussion: Discussion): Mithril.Children {
  const own = ((discussion.tags?.() || []) as any[]).filter(Boolean);
  const child = own.find((tag) => tag.isChild());
  const top = child?.parent() || own.find((tag) => tag.position() !== null) || own[0];
  const trail = [top, child].filter(Boolean);

  return (
    <nav className="HelpDeskCrumbs" aria-label={extractText(t('question.community'))}>
      <Link href={app.route('index')}>{t('question.community')}</Link>
      {trail.map((tag: any) => [
        <span className="HelpDeskCrumbs-sep" aria-hidden="true">
          /
        </span>,
        <Link href={app.route('tag', { tags: tag.slug() })}>{tag.name()}</Link>,
      ])}
    </nav>
  );
}

/** The state, who asked and when, answers and views. */
function facts(discussion: Discussion): Mithril.Children {
  const state = stateOf(discussion);
  const user = discussion.user();
  const count = answerCount(discussion);
  const views = discussion.attribute<number>('views');
  const parts: Mithril.Children[] = [
    t('question.asked', {
      date: humanTime(discussion.createdAt()!),
      username: user ? <Link href={app.route.user(user)}>{username(user)}</Link> : username(user),
    }),
    isQuestion(discussion) || data().mode === 'best' ? t('question.answers', { count }) : t('question.replies', { count }),
  ];

  if (typeof views === 'number') parts.push(t('question.views', { count: views }));

  return (
    <div className="HelpDeskFacts">
      {state && (
        <span className={'HelpDeskPill HelpDeskPill--' + state}>
          {state === 'solved' && <Icon name="fas fa-check" />}
          {stateLabel(discussion, state)}
        </span>
      )}
      <span className="HelpDeskFacts-text">{parts.map((part, i) => [i > 0 && ' · ', part])}</span>
    </div>
  );
}

/** The state's name in the words of the extension that keeps it. */
function stateLabel(discussion: Discussion, state: string): Mithril.Children {
  if (isQuestion(discussion)) {
    const names = ['unanswered', 'answered', 'solved', 'duplicate'];
    return app.translator.trans('ernestdefoe-answers.forum.state.' + names[discussion.attribute<number>('answersState') || 0]);
  }

  return app.translator.trans('fof-best-answer.forum.best_answer_button');
}
