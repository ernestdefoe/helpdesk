<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;

/**
 * A member's answer to "Did this solve it for you?": one row per member per
 * question (the table's primary key is the pair), so it has no id of its own.
 *
 * @property int $discussion_id
 * @property int $user_id
 * @property bool $helpful
 * @property Carbon $created_at
 */
class SolvedVote extends AbstractModel
{
    protected $table = 'helpdesk_feedback';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = ['helpful' => 'boolean', 'created_at' => 'datetime'];
}
