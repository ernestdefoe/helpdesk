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
 * Whose idea of "answered" the forum uses. Help Desk never decides it: an
 * implementation reads the state another extension keeps.
 */
interface Answered
{
    /** answers | best | none, handed to the frontend. */
    public function mode(): string;

    /** Does anything keep an answered state? */
    public function counts(): bool;

    /**
     * Narrow a discussions query to what counts as a question.
     *
     * @param Builder<\Flarum\Discussion\Discussion> $query
     */
    public function questions(Builder $query): void;

    /**
     * Narrow a discussions query to the answered questions.
     *
     * @param Builder<\Flarum\Discussion\Discussion> $query
     */
    public function answered(Builder $query): void;

    /** Has an answer been accepted? */
    public function solved(Discussion $discussion): bool;
}
