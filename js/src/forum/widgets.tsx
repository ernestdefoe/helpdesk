import app from 'flarum/forum/app';
import TopicCards from './components/TopicCards';
import { has, t } from './util';

/**
 * The topic cards, offered to fof/forum-widgets-core, so an admin can place
 * them in any of its areas (a tag page's sidebar, say).
 *
 * 🚨 Resolved at runtime through widget-core's registry, never imported: the
 * bundle is the same with it or without it. Registering is not placing:
 * nothing shows until an admin puts the widget somewhere.
 */
export default function registerWidgets() {
  if (!has('fof-forum-widgets-core')) return;

  const flarum = (globalThis as any).flarum;
  const widgetsMod = flarum.reg?.get?.('fof-forum-widgets-core', 'common/extend/Widgets');
  const widgetMod = flarum.reg?.get?.('fof-forum-widgets-core', 'common/components/Widget');
  const Widgets = widgetsMod?.default ?? widgetsMod;
  const WidgetBase = widgetMod?.default ?? widgetMod;

  // Present but not resolvable: a version whose internals moved. Decline quietly.
  if (!Widgets || !WidgetBase) return;

  class HelpDeskTopicsWidget extends WidgetBase {
    className() {
      return 'HelpDeskFofWidget';
    }

    icon() {
      return 'fas fa-life-ring';
    }

    title() {
      return t('topics.heading');
    }

    content() {
      return <TopicCards heading={false} />;
    }
  }

  new Widgets()
    .add({
      key: 'helpdeskTopics',
      component: HelpDeskTopicsWidget,
      isDisabled: false,
      isUnique: true,
      placement: 'end',
      position: 1,
    })
    .extend(app, 'ernestdefoe-helpdesk');
}
