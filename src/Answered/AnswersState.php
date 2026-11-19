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

/**
 * ernestdefoe/answers: its questions, and its state column (1 answered,
 * 2 solved, 3 duplicate all count as answered; 2 is an accepted answer).
 */
class AnswersState implements Answered
{
    private const SOLVED = 2;

    public function mode(): string
    {
        return 'answers';
    }

    public function counts(): bool
    {
        return true;
    }

    public function questions(Builder $query): void
    {
        $query->where('discussions.answers_question', true);
    }

    public function answered(Builder $query): void
    {
        $query->where('discussions.answers_question', true)->where('discussions.answers_state', '>=', 1);
    }

    public function solved(Discussion $discussion): bool
    {
        return (bool) $discussion->getAttribute('answers_question') && (int) $discussion->getAttribute('answers_state') === self::SOLVED;
    }
}
