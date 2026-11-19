/**
 * "/" anywhere outside a text field opens the hero's search box (the key is
 * shown beside it). Pages without the hero leave the key alone.
 */
export function slashToSearch() {
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;

    const target = e.target as HTMLElement | null;
    if (target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName))) return;

    const box = document.querySelector<HTMLElement>('.HelpDeskHero .Search-input');
    if (!box) return;

    e.preventDefault();
    box.click();
  });
}
