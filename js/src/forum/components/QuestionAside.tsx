import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
import { isQuestion, stateOf, t } from '../util';

/*
 * The question page's side column. Its numbers are fields of the discussion
 * itself (helpdeskYes, helpdeskHelpful, helpdeskGuides), sent with the
 * discussion page and only there, so showing them costs no request; an answer
 * to "Did this solve it?" is saved on the discussion (Solved.answer).
 */

/** Scroll to an element of the question, below the header. */
function jump(selector: string) {
  const el = document.querySelector(selector);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/** "On this question": the question, its accepted answer, the rest. */
export class Contents extends Component<{ discussion: Discussion }> {
  view() {
    return (
      <nav className="HelpDeskToc" aria-labelledby="helpdesk-toc">
        <h2 className="HelpDeskSide-heading" id="helpdesk-toc">
          {t('toc.heading')}
        </h2>
        <ul>
          {this.links().map(([label, go]) => (
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

  links(): [Mithril.Children, () => void][] {
    const discussion = this.attrs.discussion;
    const question = isQuestion(discussion);
    const links: [Mithril.Children, () => void][] = [[t('toc.question'), () => window.scrollTo({ top: 0, behavior: 'smooth' })]];

    if (stateOf(discussion) === 'solved') links.push([t('toc.accepted'), () => jump(question ? '.AnswersPost--accepted' : '.helpdesk-accepted')]);

    if (question) {
      links.push([t('toc.answers'), () => jump('.Answers-bar')]);
    } else if ((discussion.commentCount() || 0) > 1) {
      links.push([t('toc.replies'), () => (app.current.get('stream') as any)?.goToNumber?.(2)]);
    }

    return links;
  }
}

/** "Did this solve it for you?", under a solved question only. */
export class Solved extends Component<{ discussion: Discussion }> {
  saving = false;

  view() {
    const discussion = this.attrs.discussion;
    const yes = discussion.attribute<number | undefined>('helpdeskYes');

    // The server sends the numbers only under an accepted answer.
    if (typeof yes !== 'number') return null;

    return (
      <section className="HelpDeskSolved" aria-labelledby="helpdesk-solved">
        <h2 className="HelpDeskSolved-question" id="helpdesk-solved">
          {t('solved.question')}
        </h2>
        {this.answerView(discussion.attribute<boolean | null>('helpdeskHelpful'))}
        <p className="HelpDeskSolved-count">{t('solved.count', { count: yes })}</p>
      </section>
    );
  }

  answerView(mine: boolean | null) {
    if (mine === null || mine === undefined) {
      return (
        <div className="HelpDeskSolved-buttons">
          <Button className="Button HelpDeskSolved-button" disabled={this.saving} onclick={() => this.answer(true)}>
            {t('solved.yes')}
          </Button>
          <Button className="Button HelpDeskSolved-button" disabled={this.saving} onclick={() => this.answer(false)}>
            {t('solved.no')}
          </Button>
        </div>
      );
    }

    return (
      <p className="HelpDeskSolved-thanks" aria-live="polite">
        {mine ? t('solved.thanks_yes') : t('solved.thanks_no')}{' '}
        <button type="button" className="HelpDeskSolved-undo" disabled={this.saving} onclick={() => this.answer(null)}>
          {t('solved.undo')}
        </button>
      </p>
    );
  }

  /** Saved as a field of the discussion (PATCH /api/discussions/{id}); the reply refreshes the numbers. */
  answer(helpful: boolean | null) {
    if (!app.session.user) {
      app.modal.show(() => import('flarum/forum/components/LogInModal'));
      return;
    }

    this.saving = true;
    this.attrs.discussion
      .save({ helpdeskHelpful: helpful })
      .catch(() => {
        /* app.request has already shown the error alert */
      })
      .finally(() => {
        this.saving = false;
        m.redraw();
      });
  }
}

/** "From the knowledge base": up to three articles about the same thing. */
export class Guides extends Component<{ discussion: Discussion }> {
  view() {
    const articles = this.attrs.discussion.attribute<{ title: string; url: string }[] | undefined>('helpdeskGuides');
    if (!articles?.length) return null;

    return (
      <section className="HelpDeskGuides" aria-labelledby="helpdesk-guides">
        <h2 className="HelpDeskSide-heading" id="helpdesk-guides">
          {t('kb.heading')}
        </h2>
        <ul>
          {articles.map((article) => (
            <li>
              <Link className="HelpDeskGuides-link" href={article.url} external={true}>
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
