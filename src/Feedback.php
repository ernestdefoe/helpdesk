<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Carbon\Carbon;
use ErnestDefoe\HelpDesk\Answered\Answered;
use Flarum\Discussion\Discussion;
use Flarum\User\User;

/**
 * "Did this solve it for you?" under a solved question.
 *
 * Asked only where an answer has been accepted, and "solved" is read from
 * whoever keeps it (Answered\*). Help Desk never decides it.
 */
class Feedback
{
    public function __construct(protected Answered $answered)
    {
    }

    public function asks(Discussion $discussion): bool
    {
        return $this->answered->solved($discussion);
    }

    /** How many people said yes. */
    public function yes(Discussion $discussion): int
    {
        return SolvedVote::query()->where('discussion_id', $discussion->id)->where('helpful', true)->count();
    }

    /** This member's own answer; null when they haven't answered (or are a guest). */
    public function mine(Discussion $discussion, User $actor): ?bool
    {
        if ($actor->isGuest()) {
            return null;
        }

        $helpful = SolvedVote::query()->where('discussion_id', $discussion->id)->where('user_id', $actor->id)->value('helpful');

        return $helpful === null ? null : (bool) $helpful;
    }

    /** Yes, no, or null to take an answer back. */
    public function record(Discussion $discussion, User $actor, ?bool $helpful): void
    {
        $key = ['discussion_id' => $discussion->id, 'user_id' => $actor->id];

        if ($helpful === null) {
            SolvedVote::query()->where($key)->delete();

            return;
        }

        SolvedVote::query()->upsert([$key + ['helpful' => $helpful, 'created_at' => Carbon::now()]], ['discussion_id', 'user_id'], ['helpful']);
    }
}
