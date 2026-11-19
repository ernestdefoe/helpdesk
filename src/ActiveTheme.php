<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Is this visitor looking at Help Desk? Without ernestdefoe/wardrobe, always:
 * Help Desk is the forum's theme. With Wardrobe, WardrobeActiveTheme answers
 * instead (bound in extend.php only when Wardrobe is enabled).
 */
class ActiveTheme
{
    public function for(ServerRequestInterface $request): bool
    {
        return true;
    }
}
