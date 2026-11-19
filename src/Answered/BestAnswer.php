<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Answered;

use Flarum\Discussion\Discussion;
use Illuminate\Database\Eloquent\Builder;

/** fof/best-answer: every discussion is a question; a best answer post answers it. */
class BestAnswer implements Answered
{
    public function mode(): string
    {
        return 'best';
    }

    public function counts(): bool
    {
        return true;
    }

    public function questions(Builder $query): void
    {
    }

    public function answered(Builder $query): void
    {
        $query->whereNotNull('discussions.best_answer_post_id');
    }

    public function solved(Discussion $discussion): bool
    {
        return $discussion->getAttribute('best_answer_post_id') !== null;
    }
}
