<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/*
 * "Did this solve it for you?": one row per member per question.
 *
 * 🚨 No hand-named keys: Laravel does not prefix an explicit name, and two
 * forums sharing one database would collide on it.
 */
return Migration::createTable('helpdesk_feedback', function (Blueprint $table) {
    $table->unsignedInteger('discussion_id');
    $table->unsignedInteger('user_id');
    $table->boolean('helpful');
    $table->dateTime('created_at');

    $table->primary(['discussion_id', 'user_id']);
    $table->index('user_id');
    $table->foreign('discussion_id')->references('id')->on('discussions')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});
