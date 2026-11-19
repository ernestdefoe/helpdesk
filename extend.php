<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ErnestDefoe\HelpDesk\ActiveTheme;
use ErnestDefoe\HelpDesk\Answered;
use ErnestDefoe\HelpDesk\Api\DiscussionFields;
use ErnestDefoe\HelpDesk\Content\Page;
use ErnestDefoe\HelpDesk\Extend\Bind;
use ErnestDefoe\HelpDesk\Guides;
use ErnestDefoe\HelpDesk\Look;
use ErnestDefoe\HelpDesk\NoTopics;
use ErnestDefoe\HelpDesk\TopicNumbers;
use ErnestDefoe\HelpDesk\Topics;
use ErnestDefoe\HelpDesk\WardrobeActiveTheme;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Extend;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;

/*
 * Help Desk: a search-first support theme.
 *
 * Help Desk never decides what "answered" means. ernestdefoe/answers or
 * fof/best-answer do, and the theme styles what they say. Each optional
 * extension is wired in with Extend\Conditional below, so with none of them
 * installed the answered parts of the theme are simply not drawn.
 *
 * 🚨 Setting keys are written out as strings, never read from class
 * constants: extend.php runs on every request, and a constant lookup here
 * would fail the whole site while composer swaps the files during an update.
 */
return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->content(Page::class),

    /*
     * Where the fonts are, as an absolute URL baked into the stylesheet.
     * 🚨 Not a relative url(): Wardrobe serves each theme's CSS from its own
     * path, and a relative URL resolved against that 404s every font.
     */
    (new Extend\Theme())
        ->addCustomLessVariable('helpdesk-fonts', fn () => '"'.resolve(Filesystem::class)->disk('flarum-assets')->url('extensions/ernestdefoe-helpdesk/fonts/').'"'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(DiscussionFields::class),

    // What each optional extension adds. The defaults first; a later binding
    // wins, so with both installed Answers decides "answered", not Best Answer.
    new Bind(Answered\Answered::class, Answered\Nothing::class),
    new Bind(Guides\Guides::class, Guides\NoGuides::class),
    new Bind(TopicNumbers::class, NoTopics::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-tags', fn () => [new Bind(TopicNumbers::class, Topics::class)])
        ->whenExtensionEnabled('fof-best-answer', fn () => [new Bind(Answered\Answered::class, Answered\BestAnswer::class)])
        ->whenExtensionEnabled('ernestdefoe-answers', fn () => [new Bind(Answered\Answered::class, Answered\AnswersState::class)])
        ->whenExtensionEnabled('ernestdefoe-knowledge-base', fn () => [new Bind(Guides\Guides::class, Guides\KnowledgeBaseGuides::class)])
        ->whenExtensionEnabled('ernestdefoe-wardrobe', fn () => [new Bind(ActiveTheme::class, WardrobeActiveTheme::class)]),

    (new Extend\Settings())
        ->default('ernestdefoe-helpdesk.fonts', true)
        ->default('ernestdefoe-helpdesk.topics', true)
        ->default('ernestdefoe-helpdesk.topic_count', 6)
        ->default('ernestdefoe-helpdesk.popular', '')
        ->default('ernestdefoe-helpdesk.hero_title', '')
        ->default('ernestdefoe-helpdesk.hero_text', '')

        // read by less/lib/tokens.less; Look keeps the theme's own colours for
        // an empty or malformed value
        ->registerLessConfigVar('helpdesk-accent', 'ernestdefoe-helpdesk.accent', fn ($v) => Look::color($v, Look::ACCENT))
        ->registerLessConfigVar('helpdesk-bar', 'ernestdefoe-helpdesk.bar', fn ($v) => Look::color($v, Look::BAR))

        // read by the hero (components/Hero.tsx)
        ->serializeToForum('helpdeskHeroTitle', 'ernestdefoe-helpdesk.hero_title')
        ->serializeToForum('helpdeskHeroText', 'ernestdefoe-helpdesk.hero_text')
        ->serializeToForum('helpdeskPopular', 'ernestdefoe-helpdesk.popular', fn ($v) => array_values(array_filter(array_map('trim', explode(',', (string) $v)))))
        // read by the index page (index.tsx)
        ->serializeToForum('helpdeskTopics', 'ernestdefoe-helpdesk.topics', 'boolval')
        ->serializeToForum('helpdeskTopicCount', 'ernestdefoe-helpdesk.topic_count', fn ($v) => max(2, min(24, (int) $v))),
];
