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

/** Knowledge base articles: the hero's guide count and the articles beside a question. */
interface Guides
{
    /** Published articles this user can read, or null without a knowledge base. */
    public function count(User $actor): ?int;

    /**
     * Up to three articles about the same thing as this question.
     *
     * @return list<array{title: string, url: string}>
     */
    public function related(Discussion $discussion, User $actor): array;
}
