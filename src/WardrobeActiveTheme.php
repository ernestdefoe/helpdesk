<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Ernestdefoe\Wardrobe\ThemeRegistry;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * With ernestdefoe/wardrobe each visitor picks a theme, so Help Desk's
 * per-page server work (the font preload, the toolbar colour, the topic
 * numbers, the question page's fields) runs only for visitors on Help Desk:
 * one on Bespoke or Warren pays nothing for Help Desk being installed.
 */
class WardrobeActiveTheme extends ActiveTheme
{
    public const ID = 'ernestdefoe-helpdesk';

    public function __construct(protected ThemeRegistry $registry, protected LoggerInterface $log)
    {
    }

    public function for(ServerRequestInterface $request): bool
    {
        $cookie = $request->getCookieParams()['wardrobe_theme'] ?? null;

        try {
            $chosen = $this->registry->resolve(RequestUtil::getActor($request), is_string($cookie) ? $cookie : null);
        } catch (\Throwable $e) {
            // Serve Help Desk's extras rather than a broken page, and say why.
            $this->log->warning('[helpdesk] Wardrobe could not resolve the theme: '.$e->getMessage());

            return true;
        }

        // null: Wardrobe is serving the shared stylesheet, which has every
        // enabled theme in it, Help Desk included.
        return $chosen === null || $chosen === self::ID;
    }
}
