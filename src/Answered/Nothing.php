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

/** Neither Answers nor Best Answer: the answered parts of the theme aren't drawn. */
class Nothing implements Answered
{
    public function mode(): string
    {
        return 'none';
    }

    public function counts(): bool
    {
        return false;
    }

    public function questions(Builder $query): void
    {
    }

    public function answered(Builder $query): void
    {
    }

    public function solved(Discussion $discussion): bool
    {
        return false;
    }
}
