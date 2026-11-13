import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import Button from 'flarum/common/components/Button';
import type Discussion from 'flarum/common/models/Discussion';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import { childrenOf, t, topics } from '../util';

/**
 * The topic tree at the left of a question: every topic, the question's own
 * open with its sub-topics, like the contents of a set of docs. Ends with the
 * same "Ask a question" button as the home page, run by core's own action.
 */
export default class TopicTree extends Component<{ discussion: Discussion }> {
  view() {
    const all = topics();
    const own = (this.attrs.discussion.tags?.() || []).filter(Boolean) as any[];
    const ownTop = own.map((tag) => (tag.isChild() ? tag.parent() : tag)).find((tag) => tag && tag.position() !== null);
    const ownChild = own.find((tag) => tag.isChild());

    return (
      <nav className="HelpDeskTree" aria-labelledby="helpdesk-tree">
        <h2 className="HelpDeskTree-heading" id="helpdesk-tree">
          {t('tree.heading')}
        </h2>
        <ul>
          {all.map((tag) => {
            const open = tag === ownTop;
            const children = open ? childrenOf(tag) : [];

            return (
              <li key={tag.id()}>
                <Link
                  className={'HelpDeskTree-link' + (open ? ' is-open' : '')}
                  href={app.route('tag', { tags: tag.slug() })}
                  aria-current={open && !ownChild ? 'page' : undefined}
                >
                  {tag.name()}
                </Link>
                {children.length > 0 && (
                  <ul className="HelpDeskTree-children">
                    {children.map((child) => (
                      <li key={child.id()}>
                        <Link
                          className={'HelpDeskTree-link' + (child === ownChild ? ' is-current' : '')}
                          href={app.route('tag', { tags: child.slug() })}
                          aria-current={child === ownChild ? 'page' : undefined}
                        >
                          {child.name()}
                        </Link>
                      </li>
                    ))}
                  </ul>
                )}
              </li>
            );
          })}
        </ul>
        <Button
          className="Button Button--primary HelpDeskTree-ask"
          onclick={() => (IndexSidebar.prototype as any).newDiscussionAction.call({ attrs: {} }).catch(() => {})}
        >
          {t('ask_button')}
        </Button>
      </nav>
    );
  }
}
