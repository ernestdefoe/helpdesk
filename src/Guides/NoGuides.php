<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Guides;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

/** No knowledge base installed. */
class NoGuides implements Guides
{
    public function count(User $actor): ?int
    {
        return null;
    }

    public function related(Discussion $discussion, User $actor): array
    {
        return [];
    }
}
