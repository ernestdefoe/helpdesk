<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Content;

use ErnestDefoe\HelpDesk\ActiveTheme;
use ErnestDefoe\HelpDesk\Answered\Answered;
use ErnestDefoe\HelpDesk\Guides\Guides;
use ErnestDefoe\HelpDesk\Look;
use ErnestDefoe\HelpDesk\TopicNumbers;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Every forum page for a visitor on Help Desk: the font stamp and preload,
 * the browser toolbar colour, and the numbers the hero and the topic cards
 * show (cached, and only for the tags this visitor can see).
 */
class Page
{
    public function __construct(
        protected ActiveTheme $theme,
        protected SettingsRepositoryInterface $settings,
        protected Answered $answered,
        protected TopicNumbers $topics,
        protected Guides $guides,
        protected Filesystem $filesystem,
    ) {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        // A visitor Wardrobe is serving another theme gets none of this.
        if (! $this->theme->for($request)) {
            return;
        }

        $actor = RequestUtil::getActor($request);
        $fonts = (bool) $this->settings->get('ernestdefoe-helpdesk.fonts');

        $document->extraAttributes['data-helpdesk-fonts'] = $fonts ? 'on' : 'off';

        // Mobile browsers tint their toolbar from theme-color.
        $document->meta['theme-color'] = Look::color($this->settings->get('ernestdefoe-helpdesk.bar'), Look::BAR);

        $document->payload['helpdesk'] = [
            'mode' => $this->answered->mode(),
            'topics' => (object) $this->topics->for($actor),
            'guides' => $this->guides->count($actor),
        ];

        if ($fonts) {
            $url = $this->filesystem->disk('flarum-assets')->url('extensions/ernestdefoe-helpdesk/fonts/ibm-plex-sans-latin-wght-normal.woff2');
            $document->head[] = '<link rel="preload" href="'.e($url).'" as="font" type="font/woff2" crossorigin>';
        }
    }
}
