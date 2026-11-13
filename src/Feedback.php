<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;

/**
 * "Did this solve it for you?" under a solved question.
 *
 * Asked only where an answer has been accepted, and "solved" is read from
 * whoever keeps it: ernestdefoe/answers' state, or fof/best-answer's best
 * post. Help Desk never decides it.
 */
class Feedback
{
    public function __construct(protected ConnectionInterface $db, protected Topics $topics)
    {
    }

    public function asks(Discussion $discussion): bool
    {
        return match ($this->topics->mode()) {
            'answers' => (bool) $discussion->getAttribute('answers_question') && (int) $discussion->getAttribute('answers_state') === 2,
            'best' => $discussion->getAttribute('best_answer_post_id') !== null,
            default => false,
        };
    }

    /**
     * The yes count and this member's own answer (null: not answered), in
     * one query.
     *
     * @return array{yes: int, mine: bool|null}
     */
    public function read(Discussion $discussion, User $actor): array
    {
        $grammar = $this->db->getQueryGrammar();
        $helpful = $grammar->wrap('helpful');
        // 2 yes, 1 no. Not `helpful + 1`: PostgreSQL won't add to a boolean.
        $mine = $actor->isGuest() ? 'NULL' : 'MAX(CASE WHEN '.$grammar->wrap('user_id').' = '.(int) $actor->id." THEN (CASE WHEN $helpful THEN 2 ELSE 1 END) END)";

        $row = $this->db->table('helpdesk_feedback')
            ->where('discussion_id', $discussion->id)
            ->selectRaw("SUM(CASE WHEN $helpful THEN 1 ELSE 0 END) as yes")
            ->selectRaw("$mine as mine")
            ->first();

        $mineValue = $row?->mine === null ? null : ((int) $row->mine) === 2;

        return ['yes' => (int) ($row->yes ?? 0), 'mine' => $mineValue];
    }

    /** Yes, no, or null to take an answer back. */
    public function record(Discussion $discussion, User $actor, ?bool $helpful): void
    {
        $table = $this->db->table('helpdesk_feedback');
        $key = ['discussion_id' => $discussion->id, 'user_id' => $actor->id];

        if ($helpful === null) {
            $table->where($key)->delete();

            return;
        }

        $table->upsert([$key + ['helpful' => $helpful, 'created_at' => Carbon::now()]], ['discussion_id', 'user_id'], ['helpful']);
    }
}
