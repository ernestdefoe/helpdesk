<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Guides;

use Flarum\Discussion\Discussion;
use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;

/**
 * ernestdefoe/knowledge-base: the guide count in the hero and the articles
 * beside a question.
 *
 * Bound only when the knowledge base is enabled (Extend\Conditional in
 * extend.php). Its classes are named as strings because it is not a Composer
 * dependency of Help Desk, so nothing here is loaded until it is needed.
 */
class KnowledgeBaseGuides implements Guides
{
    private const ARTICLE = 'ErnestDefoe\\KnowledgeBase\\Model\\Article';

    private const INDEXING = 'ErnestDefoe\\KnowledgeBase\\Service\\Indexing';

    private const TTL = 600;

    /** How many recent published titles the word match looks through. */
    private const TITLES = 2000;

    /** Words too common to say what a question is about. */
    private const STOP = ['about', 'after', 'again', 'also', 'been', 'before', 'being', 'cannot', 'could', 'does', 'doesn', 'every', 'from', 'have', 'help', 'here', 'into', 'just', 'like', 'make', 'many', 'more', 'most', 'much', 'need', 'only', 'other', 'over', 'same', 'should', 'some', 'still', 'than', 'that', 'their', 'them', 'then', 'there', 'these', 'they', 'this', 'what', 'when', 'where', 'which', 'while', 'with', 'work', 'works', 'would', 'your'];

    public function __construct(
        protected Cache $cache,
        protected Container $container,
        protected UrlGenerator $url,
        protected LoggerInterface $log,
    ) {
    }

    public function count(User $actor): ?int
    {
        if (! $actor->hasPermission('kb.view')) {
            return null;
        }

        return (int) $this->cache->remember('ernestdefoe-helpdesk.kb-count', self::TTL, fn () => (self::ARTICLE)::query()->visible()->count());
    }

    /**
     * The matching ids are cached per question (they change only when the
     * title does); the articles are then read through the knowledge base's
     * own `visible` scope, so a draft never shows, whatever the cache holds.
     */
    public function related(Discussion $discussion, User $actor): array
    {
        if (! $actor->hasPermission('kb.view')) {
            return [];
        }

        $title = (string) $discussion->title;
        $ids = $this->cache->remember('ernestdefoe-helpdesk.kb.'.$discussion->id.'.'.md5($title), 3600, fn () => $this->match($title));

        if (! $ids) {
            return [];
        }

        $articles = (self::ARTICLE)::query()->visible()->whereIn('id', $ids)->get(['id', 'title', 'slug'])->keyBy('id');
        $forum = $this->url->to('forum');

        return collect($ids)
            ->map(fn (int $id) => $articles->get($id))
            ->filter()
            ->map(fn ($article) => ['title' => (string) $article->title, 'url' => $forum->route('kb.article', ['slug' => $article->slug])])
            ->values()
            ->all();
    }

    /** @return list<int> */
    protected function match(string $title): array
    {
        $words = $this->words($title);

        if (! $words) {
            return [];
        }

        // The knowledge base's own search first: with OpenSearch behind it,
        // it ranks far better than a word match.
        $ids = $this->search(implode(' ', $words));

        if ($ids) {
            return $ids;
        }

        // Otherwise match the question's words against recent article titles,
        // in PHP: a LIKE '%word%' per word would scan the whole table.
        return collect($this->titles())
            ->map(fn (string $articleTitle) => count(array_filter($words, fn (string $word) => str_contains($articleTitle, $word))))
            ->filter()
            ->sortDesc()
            ->keys()
            ->take(3)
            ->map('intval')
            ->values()
            ->all();
    }

    /** @return list<int> */
    protected function search(string $query): array
    {
        try {
            return array_values(array_map('intval', array_slice($this->container->make(self::INDEXING)->search($query, 3), 0, 3)));
        } catch (\Throwable $e) {
            // An index that is down is not a broken question page: fall back
            // to the word match, and say so in the log.
            $this->log->warning('[helpdesk] knowledge base search failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Article id => lowercase title, for the newest published articles.
     *
     * @return array<int, string>
     */
    protected function titles(): array
    {
        return $this->cache->remember('ernestdefoe-helpdesk.kb-titles', self::TTL, fn () => (self::ARTICLE)::query()->visible()
            ->orderByDesc('id')
            ->limit(self::TITLES)
            ->pluck('title', 'id')
            ->map(fn ($title) => mb_strtolower((string) $title))
            ->all());
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
