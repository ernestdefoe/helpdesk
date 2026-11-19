<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Flarum\User\User;

/** The topic cards' numbers: tag id => [questions, answered], for the tags a user can see. */
interface TopicNumbers
{
    /** @return array<int, array{0: int, 1: int}> */
    public function for(User $actor): array;
}
