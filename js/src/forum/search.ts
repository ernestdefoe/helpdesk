import app from 'flarum/forum/app';
import GlobalSearch from 'flarum/forum/components/GlobalSearch';

/**
 * Core's search, opened with these words in it, exactly as the header's
 * search button opens it: every source (another extension's included) and
 * whatever search driver the forum runs.
 */
export function openSearch(query: string) {
  const sources = new (GlobalSearch as any)().sourceItems().toArray();
  if (!sources.length) return;

  app.search.state.setValue(query.trim());
  app.modal.show(() => import('flarum/common/components/SearchModal'), { searchState: app.search.state, sources });
}

/** "/" anywhere outside a text field: the hero's box, or core's search where there is no hero. */
export function slashToSearch() {
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;

    const target = e.target as HTMLElement | null;
    if (target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName))) return;
    if (document.querySelector('.ModalManager .Modal')) return;

    e.preventDefault();

    const box = document.getElementById('helpdesk-search') as HTMLInputElement | null;

    if (box) box.focus();
    else openSearch('');
  });
}
