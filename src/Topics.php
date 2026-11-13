<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Flarum\Extension\ExtensionManager;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;

/**
 * How many questions each topic (tag) has, and how many are answered.
 *
 * Who decides "answered" is never Help Desk: it reads the state another
 * extension keeps. ernestdefoe/answers keeps a state column on every
 * question (answered, solved or duplicate all count); fof/best-answer keeps a
 * best answer post. With neither, there is no such thing as answered, and the
 * theme shows plain discussion counts instead.
 *
 * ONE grouped query for every tag, cached for ten minutes and shared by every
 * visitor; per visitor, only the tags they can see are handed out (one query
 * for those ids), so a restricted tag's numbers never reach anyone who cannot
 * open it.
 */
class Topics
{
    public const TTL = 600;

    public function __construct(
        protected ConnectionInterface $db,
        protected Cache $cache,
        protected ExtensionManager $extensions,
    ) {
    }

    /** answers | best | none: whose idea of "answered" this forum uses. */
    public function mode(): string
    {
        if ($this->extensions->isEnabled('ernestdefoe-answers')) {
            return 'answers';
        }

        return $this->extensions->isEnabled('fof-best-answer') ? 'best' : 'none';
    }

    /**
     * Tag id => [questions, answered], for the tags this user can see. Empty
     * when nothing keeps an answered state, or tags are off.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public function for(User $actor): array
    {
        $mode = $this->mode();

        if ($mode === 'none' || ! $this->extensions->isEnabled('flarum-tags')) {
            return [];
        }

        $all = $this->all($mode);

        if (! $all) {
            return [];
        }

        $visible = Tag::query()->whereVisibleTo($actor)->whereIn('id', array_keys($all))->pluck('id')->all();

        return array_intersect_key($all, array_flip(array_map('intval', $visible)));
    }

    /**
     * Every tag's numbers, from the cache.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public function all(string $mode): array
    {
        return $this->cache->remember('ernestdefoe-helpdesk.topics.'.$mode, self::TTL, fn () => $this->count($mode));
    }

    /** @return array<int, array{0: int, 1: int}> */
    protected function count(string $mode): array
    {
        $grammar = $this->db->getQueryGrammar();

        // 🚨 Wrapped by the grammar, so the table prefix is added: raw SQL
        // takes it verbatim and 500s on a prefixed forum.
        $answered = $mode === 'answers'
            ? $grammar->wrap('discussions.answers_state').' >= 1'
            : $grammar->wrap('discussions.best_answer_post_id').' IS NOT NULL';

        $query = $this->db->table('discussion_tag')
            ->join('discussions', 'discussions.id', '=', 'discussion_tag.discussion_id')
            ->where('discussions.is_private', false)
            ->whereNull('discussions.hidden_at')
            ->groupBy('discussion_tag.tag_id')
            ->select('discussion_tag.tag_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN $answered THEN 1 ELSE 0 END) as answered");

        if ($mode === 'answers') {
            $query->where('discussions.answers_question', true);
        }

        $out = [];

        foreach ($query->get() as $row) {
            $out[(int) $row->tag_id] = [(int) $row->total, (int) $row->answered];
        }

        return $out;
    }
}
