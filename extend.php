<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ErnestDefoe\HelpDesk\ActiveTheme;
use ErnestDefoe\HelpDesk\Api\QuestionController;
use ErnestDefoe\HelpDesk\Kb;
use ErnestDefoe\HelpDesk\Topics;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Psr\Http\Message\ServerRequestInterface;

/*
 * Help Desk: a search-first support theme.
 *
 * Help Desk never decides what "answered" means. ernestdefoe/answers or
 * fof/best-answer do, and the theme styles what they say (Topics reads it
 * for the topic cards, Feedback for "Did this solve it?"). With neither, the
 * answered parts of the theme are simply not drawn.
 *
 * 🚨 Setting keys are written out as strings, never read from class
 * constants: extend.php runs on every request, and while composer swaps the
 * files during an update, autoloading Help Desk's own classes at load time
 * would fail the whole site. (Inside closures is fine.)
 */
return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        // 🚨 CLOSURES, not strings, in extraAttributes: core calls any value
        // is_callable() says yes to, and a string can name a PHP function.
        ->content(function (Document $document, ServerRequestInterface $request) {
            // A visitor Wardrobe is serving another theme gets none of this.
            if (! resolve(ActiveTheme::class)->for($request)) {
                return;
            }

            $settings = resolve(SettingsRepositoryInterface::class);
            $actor = RequestUtil::getActor($request);
            $topics = resolve(Topics::class);
            $fonts = (bool) $settings->get('ernestdefoe-helpdesk.fonts', true);

            $document->extraAttributes['data-helpdesk-fonts'] = fn () => $fonts ? 'on' : 'off';
            // Mobile browsers tint their toolbar from theme-color: the bar's, checked
            // like the stylesheet checks it.
            $bar = (string) $settings->get('ernestdefoe-helpdesk.bar');
            $document->meta['theme-color'] = preg_match('/^#[0-9a-f]{6}$/i', $bar) ? $bar : '#0F2A33';

            // The topic cards' numbers and the hero's counts: cached, and
            // only for the tags this visitor can see.
            $document->payload['helpdesk'] = [
                'mode' => $topics->mode(),
                'topics' => (object) $topics->for($actor),
                'guides' => resolve(Kb::class)->count($actor),
            ];

            if ($fonts) {
                $assets = resolve(Filesystem::class)->disk('flarum-assets');
                $document->head[] = '<link rel="preload" href="'.e($assets->url('extensions/ernestdefoe-helpdesk/fonts/ibm-plex-sans-latin-wght-normal.woff2')).'" as="font" type="font/woff2" crossorigin>';
            }
        }),

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

    (new Extend\Routes('api'))
        ->get('/helpdesk/discussions/{id:\d+}', 'helpdesk.question', QuestionController::class)
        ->post('/helpdesk/discussions/{id:\d+}/feedback', 'helpdesk.feedback', QuestionController::class),

    (new Extend\Settings())
        ->default('ernestdefoe-helpdesk.fonts', true)
        ->default('ernestdefoe-helpdesk.topics', true)
        ->default('ernestdefoe-helpdesk.topic_count', 6)
        ->default('ernestdefoe-helpdesk.popular', '')
        ->default('ernestdefoe-helpdesk.hero_title', '')
        ->default('ernestdefoe-helpdesk.hero_text', '')

        // read by less/lib/tokens.less
        ->registerLessConfigVar('helpdesk-accent', 'ernestdefoe-helpdesk.accent', fn ($v) => preg_match('/^#[0-9a-f]{6}$/i', (string) $v) ? $v : '#0E6E6E')
        ->registerLessConfigVar('helpdesk-bar', 'ernestdefoe-helpdesk.bar', fn ($v) => preg_match('/^#[0-9a-f]{6}$/i', (string) $v) ? $v : '#0F2A33')

        // read by the hero (components/Hero.tsx)
        ->serializeToForum('helpdeskHeroTitle', 'ernestdefoe-helpdesk.hero_title')
        ->serializeToForum('helpdeskHeroText', 'ernestdefoe-helpdesk.hero_text')
        ->serializeToForum('helpdeskPopular', 'ernestdefoe-helpdesk.popular', fn ($v) => array_values(array_filter(array_map('trim', explode(',', (string) $v)))))
        // read by the index page (index.tsx)
        ->serializeToForum('helpdeskTopics', 'ernestdefoe-helpdesk.topics', 'boolval')
        ->serializeToForum('helpdeskTopicCount', 'ernestdefoe-helpdesk.topic_count', fn ($v) => max(2, min(24, (int) $v))),
];
