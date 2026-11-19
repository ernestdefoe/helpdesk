<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use ErnestDefoe\HelpDesk\Answered\Answered;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Builder;

/**
 * How many questions each topic (tag) has, and how many are answered.
 *
 * "Answered" is read from whichever extension keeps it (see Answered\*,
 * bound in extend.php). With neither, there is no such thing as answered, and
 * the theme shows plain discussion counts instead.
 *
 * ONE query for every tag (a count of its questions and of its answered
 * ones), cached for ten minutes and shared by every visitor; per visitor, only the tags they can see are handed out (one query
 * for those ids), so a restricted tag's numbers never reach anyone who cannot
 * open it. Bound only when flarum/tags is enabled (extend.php).
 */
class Topics implements TopicNumbers
{
    public const TTL = 600;

    public function __construct(
        protected Cache $cache,
        protected Answered $answered,
    ) {
    }

    /**
     * Tag id => [questions, answered], for the tags this user can see.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public function for(User $actor): array
    {
        $all = $this->all();

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
    public function all(): array
    {
        return $this->cache->remember('ernestdefoe-helpdesk.topics.'.$this->answered->mode(), self::TTL, fn () => $this->count());
    }

    /** @return array<int, array{0: int, 1: int}> */
    protected function count(): array
    {
        if (! $this->answered->counts()) {
            return [];
        }

        // Only discussions anyone may see (the cache is shared by every
        // visitor, so a restricted tag's own card shows its plain discussion
        // count instead): never hidden or private ones
        // (flarum/approval marks unapproved discussions private), and never
        // one that is also in a restricted tag, whose numbers would otherwise
        // show up on the public tag it shares.
        $public = fn (Builder $query) => $query
            ->where('discussions.is_private', false)
            ->whereNull('discussions.hidden_at')
            ->whereDoesntHave('tags', fn (Builder $tags) => $tags
                ->where('tags.is_restricted', true)
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_restricted', true)));

        $tags = Tag::query()
            ->withCount([
                'discussions as questions' => function (Builder $query) use ($public) {
                    $public($query);
                    $this->answered->questions($query);
                },
                'discussions as answered' => function (Builder $query) use ($public) {
                    $public($query);
                    $this->answered->answered($query);
                },
            ])
            ->get(['id']);

        return $tags
            ->filter(fn (Tag $tag) => $tag->getAttribute('questions') > 0)
            ->mapWithKeys(fn (Tag $tag) => [(int) $tag->id => [(int) $tag->getAttribute('questions'), (int) $tag->getAttribute('answered')]])
            ->all();
    }
}
