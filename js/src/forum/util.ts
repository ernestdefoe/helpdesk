import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';

/** A forum-side Help Desk string. Every word on screen comes through here. */
export const t = (key: string, params: Record<string, unknown> = {}) => app.translator.trans(`ernestdefoe-helpdesk.forum.${key}`, params as any);

/**
 * Whether an extension with forum JS is enabled. Never a build-time import:
 * `flarum` is reached through globalThis so its absence is `undefined`.
 */
export function has(id: string): boolean {
  const extensions = (globalThis as any).flarum?.extensions;

  return !!extensions && id in extensions;
}

export interface HelpDeskData {
  /** Whose idea of "answered" the forum uses: ernestdefoe/answers, fof/best-answer, or nobody's. */
  mode: 'answers' | 'best' | 'none';
  /** Tag id => [questions, answered], for the tags this visitor can see. */
  topics: Record<string, [number, number]>;
  /** Published knowledge base articles, or null without a knowledge base. */
  guides: number | null;
}

/** The numbers extend.php put in the page (absent for a visitor on another Wardrobe theme). */
export function data(): HelpDeskData {
  return (app.data as any).helpdesk || { mode: 'none', topics: {}, guides: null };
}

/** An Answers question: the state, counts and thread are Answers'. */
export const isQuestion = (discussion: Discussion | null | undefined): boolean => !!discussion?.attribute<boolean>('answersQuestion');

/**
 * solved | open | unanswered, read from whoever keeps it, or null when
 * nothing does (then no state is drawn at all).
 */
export function stateOf(discussion: Discussion): 'solved' | 'open' | 'unanswered' | null {
  if (isQuestion(discussion)) {
    const state = discussion.attribute<number>('answersState') || 0;

    return state === 2 ? 'solved' : state === 0 ? 'unanswered' : 'open';
  }

  if (has('fof-best-answer') && discussion.attribute('hasBestAnswer')) return 'solved';

  return null;
}

/** Answers (or replies, without Answers) to a discussion. */
export function answerCount(discussion: Discussion): number {
  if (isQuestion(discussion)) return discussion.attribute<number>('answersCount') || 0;

  return Math.max(0, (discussion.commentCount() || 1) - 1);
}

/** The tags extension's models, when it is on. */
export function tags(): any[] {
  return has('flarum-tags') ? app.store.all('tags') : [];
}

/** Top-level primary tags, in the admin's order: the forum's topics. */
export function topics(): any[] {
  return tags()
    .filter((tag) => tag.position() !== null && !tag.isChild())
    .sort((a, b) => a.position() - b.position());
}

/** A tag's children, in order. */
export function childrenOf(parent: any): any[] {
  return tags()
    .filter((tag) => tag.isChild() && tag.parent() === parent && tag.position() !== null)
    .sort((a, b) => a.position() - b.position());
}

export const fmt = (n: number) => (n || 0).toLocaleString();
