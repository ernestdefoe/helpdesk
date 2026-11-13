<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Tests\integration\api;

use ErnestDefoe\HelpDesk\Tests\integration\HelpDeskTestCase;
use PHPUnit\Framework\Attributes\Test;

class QuestionTest extends HelpDeskTestCase
{
    #[Test]
    public function a_solved_question_asks_and_counts()
    {
        [$status, $body] = $this->call('GET', '/api/helpdesk/discussions/1');

        $this->assertSame(200, $status);
        $this->assertSame(['yes' => 0, 'mine' => null], $body['feedback']);
        $this->assertSame([], $body['kb'], 'No knowledge base installed');
    }

    #[Test]
    public function an_unsolved_question_does_not_ask()
    {
        [$status, $body] = $this->call('GET', '/api/helpdesk/discussions/2');

        $this->assertSame(200, $status);
        $this->assertNull($body['feedback']);

        [$status] = $this->call('POST', '/api/helpdesk/discussions/2/feedback', 2, ['helpful' => true]);
        $this->assertSame(403, $status);
    }

    #[Test]
    public function a_question_the_visitor_cannot_see_is_not_found()
    {
        foreach ([3, 4, 5] as $id) {
            [$status] = $this->call('GET', "/api/helpdesk/discussions/$id");
            $this->assertSame(404, $status, "Discussion $id");
        }

        [$status] = $this->call('POST', '/api/helpdesk/discussions/3/feedback', 2, ['helpful' => true]);
        $this->assertSame(404, $status, 'A member cannot answer on a restricted tag\'s question');
    }

    #[Test]
    public function a_guest_cannot_answer()
    {
        [$status] = $this->call('POST', '/api/helpdesk/discussions/1/feedback', null, ['helpful' => true]);

        // Core's CSRF check refuses a session-less guest (400) before the
        // controller's own assertRegistered (401) would.
        $this->assertContains($status, [400, 401]);
        $this->assertSame(0, $this->database()->table('helpdesk_feedback')->count());
    }

    #[Test]
    public function a_member_answers_changes_and_takes_back()
    {
        [$status, $body] = $this->call('POST', '/api/helpdesk/discussions/1/feedback', 2, ['helpful' => true]);
        $this->assertSame(200, $status);
        $this->assertSame(['yes' => 1, 'mine' => true], $body['feedback']);

        [, $body] = $this->call('POST', '/api/helpdesk/discussions/1/feedback', 3, ['helpful' => true]);
        $this->assertSame(['yes' => 2, 'mine' => true], $body['feedback']);

        [, $body] = $this->call('POST', '/api/helpdesk/discussions/1/feedback', 2, ['helpful' => false]);
        $this->assertSame(['yes' => 1, 'mine' => false], $body['feedback'], 'One row per member: a change replaces it');

        [, $body] = $this->call('POST', '/api/helpdesk/discussions/1/feedback', 2, ['helpful' => null]);
        $this->assertSame(['yes' => 1, 'mine' => null], $body['feedback']);

        [, $body] = $this->call('GET', '/api/helpdesk/discussions/1');
        $this->assertSame(['yes' => 1, 'mine' => null], $body['feedback'], 'A guest sees the count, never a member\'s answer');
    }
}
