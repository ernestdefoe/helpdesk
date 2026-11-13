<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;

/**
 * ernestdefoe/knowledge-base, when it is there: the guide count in the hero
 * and the articles beside a question.
 *
 * 🚨 Soft: the knowledge base is never required, and its classes are named as
 * strings, so Help Desk loads the same with it or without it.
 */
class Kb
{
    private const ARTICLE = 'ErnestDefoe\\KnowledgeBase\\Model\\Article';

    private const INDEXING = 'ErnestDefoe\\KnowledgeBase\\Service\\Indexing';

    /** Words too common to say what a question is about. */
    private const STOP = ['about', 'after', 'again', 'also', 'been', 'before', 'being', 'cannot', 'could', 'does', 'doesn', 'every', 'from', 'have', 'help', 'here', 'into', 'just', 'like', 'make', 'many', 'more', 'most', 'much', 'need', 'only', 'other', 'over', 'same', 'should', 'some', 'still', 'than', 'that', 'their', 'them', 'then', 'there', 'these', 'they', 'this', 'what', 'when', 'where', 'which', 'while', 'with', 'work', 'works', 'would', 'your'];

    public function __construct(
        protected ExtensionManager $extensions,
        protected Cache $cache,
        protected Container $container,
    ) {
    }

    public function on(User $actor): bool
    {
        return $this->extensions->isEnabled('ernestdefoe-knowledge-base')
            && class_exists(self::ARTICLE)
            && $actor->hasPermission('kb.view');
    }

    /** Published articles, for the hero's "and 186 guides". */
    public function count(User $actor): ?int
    {
        if (! $this->on($actor)) {
            return null;
        }

        return (int) $this->cache->remember('ernestdefoe-helpdesk.kb-count', Topics::TTL, fn () => (self::ARTICLE)::query()->visible()->count());
    }

    /**
     * Up to three articles about the same thing as this question.
     *
     * The matching ids are cached per question (they change only when the
     * title does); the articles are then read through the knowledge base's
     * own `visible` scope, so a draft never shows, whatever the cache holds.
     *
     * @return list<array{title: string, slug: string}>
     */
    public function related(Discussion $discussion, User $actor): array
    {
        if (! $this->on($actor)) {
            return [];
        }

        $title = (string) $discussion->title;
        $key = 'ernestdefoe-helpdesk.kb.'.$discussion->id.'.'.md5($title);
        $ids = $this->cache->remember($key, 3600, fn () => $this->match($title));

        if (! $ids) {
            return [];
        }

        $articles = (self::ARTICLE)::query()->visible()->whereIn('id', $ids)->get(['id', 'title', 'slug'])->keyBy('id');

        $out = [];

        foreach ($ids as $id) {
            if ($article = $articles->get($id)) {
                $out[] = ['title' => (string) $article->title, 'slug' => (string) $article->slug];
            }
        }

        return $out;
    }

    /** @return list<int> */
    protected function match(string $title): array
    {
        $words = $this->words($title);

        if (! $words) {
            return [];
        }

        // The knowledge base's own search first: with OpenSearch behind it,
        // it ranks far better than anything here. Without it, it looks for
        // the whole title as one phrase, which a question title never is,
        // and the word match below takes over.
        if (class_exists(self::INDEXING)) {
            try {
                $ids = $this->container->make(self::INDEXING)->search(implode(' ', $words), 3);

                if ($ids) {
                    return array_values(array_map('intval', array_slice($ids, 0, 3)));
                }
            } catch (\Throwable) {
                // An index that is down is not a broken question page.
            }
        }

        $like = fn (string $word) => '%'.str_replace(['%', '_'], ['\%', '\_'], $word).'%';

        $candidates = (self::ARTICLE)::query()->visible()
            ->where(function ($q) use ($words, $like) {
                foreach ($words as $word) {
                    $q->orWhere('title', 'like', $like($word));
                }
            })
            ->limit(40)
            ->get(['id', 'title']);

        $scored = [];

        foreach ($candidates as $article) {
            $hay = mb_strtolower((string) $article->title);
            $score = 0;

            foreach ($words as $word) {
                $score += str_contains($hay, $word) ? 1 : 0;
            }

            $scored[(int) $article->id] = $score;
        }

        arsort($scored);

        return array_slice(array_keys($scored), 0, 3);
    }

    /** @return list<string> */
    protected function words(string $title): array
    {
        preg_match_all('/[\p{L}\p{N}]{4,}/u', mb_strtolower($title), $m);

        $words = array_values(array_unique(array_diff($m[0], self::STOP)));

        // Longer words say more about a question than short ones.
        usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return array_slice($words, 0, 6);
    }
}
