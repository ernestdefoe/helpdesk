<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Flarum\User\User;

/** flarum/tags is off: there are no topics to count. */
class NoTopics implements TopicNumbers
{
    public function for(User $actor): array
    {
        return [];
    }
}
