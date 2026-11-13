import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import type Discussion from 'flarum/common/models/Discussion';
import { has, isQuestion, stateOf, t } from '../util';

interface Side {
  kb: { title: string; slug: string }[] | null;
  feedback: { yes: number; mine: boolean | null } | null;
}

/**
 * One request per question page for the side column (related articles and
 * the "solved it" numbers), kept per discussion so moving between the
 * question and other pages never asks twice.
 */
const cache = new Map<string, Side>();
const pending = new Map<string, Promise<void>>();

function load(discussion: Discussion): Side | null {
  const id = discussion.id()!;
  if (cache.has(id)) return cache.get(id)!;

  if (!pending.has(id)) {
    pending.set(
      id,
      app
        .request<Side>({ method: 'GET', url: `${app.forum.attribute('apiUrl')}/helpdesk/discussions/${id}`, errorHandler: () => {} })
        .then((side) => {
          cache.set(id, side);
          m.redraw();
        })
        .catch(() => {
          cache.set(id, { kb: [], feedback: null });
        })
    );
  }

  return null;
}

/** Scroll to an element of the question, below the header. */
function jump(selector: string) {
  const el = document.querySelector(selector);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/** "On this question": the question, its accepted answer, the rest. */
export class Contents extends Component<{ discussion: Discussion }> {
  view() {
    const discussion = this.attrs.discussion;
    const question = isQuestion(discussion);
    const solved = stateOf(discussion) === 'solved';
    const links: [any, () => void][] = [[t('toc.question'), () => window.scrollTo({ top: 0, behavior: 'smooth' })]];

    if (solved) links.push([t('toc.accepted'), () => jump(question ? '.AnswersPost--accepted' : '.helpdesk-accepted')]);

    if (question) {
      links.push([t('toc.answers'), () => jump('.Answers-bar')]);
    } else if ((discussion.commentCount() || 0) > 1) {
      links.push([t('toc.replies'), () => (app.current.get('stream') as any)?.goToNumber?.(2)]);
    }

    return (
      <nav className="HelpDeskToc" aria-labelledby="helpdesk-toc">
        <h2 className="HelpDeskSide-heading" id="helpdesk-toc">
          {t('toc.heading')}
        </h2>
        <ul>
          {links.map(([label, go]) => (
            <li>
              <button type="button" className="HelpDeskToc-link" onclick={go}>
                {label}
              </button>
            </li>
          ))}
        </ul>
      </nav>
    );
  }
}

/** "Did this solve it for you?", under a solved question only. */
export class Solved extends Component<{ discussion: Discussion }> {
  saving = false;

  view() {
    // Only an accepted answer is asked about; nothing is fetched otherwise.
    if (stateOf(this.attrs.discussion) !== 'solved') return null;

    const side = load(this.attrs.discussion);
    const feedback = side?.feedback;
    if (!feedback) return null;

    const answer = (helpful: boolean | null) => {
      if (!app.session.user) {
        app.modal.show(() => import('flarum/forum/components/LogInModal'));
        return;
      }

      this.saving = true;
      app
        .request<Side>({
          method: 'POST',
          url: `${app.forum.attribute('apiUrl')}/helpdesk/discussions/${this.attrs.discussion.id()}/feedback`,
          body: { helpful },
        })
        .then((next) => {
          cache.set(this.attrs.discussion.id()!, { kb: side!.kb, feedback: next.feedback });
        })
        .finally(() => {
          this.saving = false;
          m.redraw();
        });
    };

    return (
      <section className="HelpDeskSolved" aria-labelledby="helpdesk-solved">
        <h2 className="HelpDeskSolved-question" id="helpdesk-solved">
          {t('solved.question')}
        </h2>
        {feedback.mine === null ? (
          <div className="HelpDeskSolved-buttons">
            <Button className="Button HelpDeskSolved-button" disabled={this.saving} onclick={() => answer(true)}>
              {t('solved.yes')}
            </Button>
            <Button className="Button HelpDeskSolved-button" disabled={this.saving} onclick={() => answer(false)}>
              {t('solved.no')}
            </Button>
          </div>
        ) : (
          <p className="HelpDeskSolved-thanks" aria-live="polite">
            {feedback.mine ? t('solved.thanks_yes') : t('solved.thanks_no')}{' '}
            <button type="button" className="HelpDeskSolved-undo" disabled={this.saving} onclick={() => answer(null)}>
              {t('solved.undo')}
            </button>
          </p>
        )}
        <p className="HelpDeskSolved-count">{t('solved.count', { count: feedback.yes })}</p>
      </section>
    );
  }
}

/** "From the knowledge base": up to three articles about the same thing. */
export class Guides extends Component<{ discussion: Discussion }> {
  view() {
    if (!has('ernestdefoe-knowledge-base')) return null;

    const articles = load(this.attrs.discussion)?.kb;
    if (!articles?.length) return null;

    const href = (slug: string) => (app.routes['kb.article'] ? app.route('kb.article', { slug }) : app.forum.attribute('baseUrl') + '/kb/' + slug);

    return (
      <section className="HelpDeskGuides" aria-labelledby="helpdesk-guides">
        <h2 className="HelpDeskSide-heading" id="helpdesk-guides">
          {t('kb.heading')}
        </h2>
        <ul>
          {articles.map((article) => (
            <li>
              <Link className="HelpDeskGuides-link" href={href(article.slug)}>
                <Icon name="far fa-file-alt" />
                <span>{article.title}</span>
              </Link>
            </li>
          ))}
        </ul>
      </section>
    );
  }
}
