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
    /** @return array{0: int, 1: mixed} */
    private function answer(int $discussion, ?int $actor, ?bool $helpful): array
    {
        return $this->call('PATCH', "/api/discussions/$discussion", $actor, ['data' => ['type' => 'discussions', 'id' => (string) $discussion, 'attributes' => ['helpdeskHelpful' => $helpful]]]);
    }

    #[Test]
    public function a_solved_question_shows_the_numbers_on_its_own_page()
    {
        [$status, $body] = $this->call('GET', '/api/discussions/1');

        $this->assertSame(200, $status);
        $this->assertSame(0, $body['data']['attributes']['helpdeskYes']);
        $this->assertNull($body['data']['attributes']['helpdeskHelpful']);
        $this->assertSame([], $body['data']['attributes']['helpdeskGuides'], 'No knowledge base installed');
    }

    #[Test]
    public function lists_carry_none_of_it()
    {
        [$status, $body] = $this->call('GET', '/api/discussions');

        $this->assertSame(200, $status);
        foreach ($body['data'] as $discussion) {
            $this->assertArrayNotHasKey('helpdeskYes', $discussion['attributes']);
            $this->assertArrayNotHasKey('helpdeskGuides', $discussion['attributes']);
        }
    }

    #[Test]
    public function an_unsolved_question_does_not_ask()
    {
        [, $body] = $this->call('GET', '/api/discussions/2');
        $this->assertArrayNotHasKey('helpdeskYes', $body['data']['attributes']);

        [$status] = $this->answer(2, 2, true);
        $this->assertSame(403, $status);
        $this->assertSame(0, $this->database()->table('helpdesk_feedback')->count());
    }

    #[Test]
    public function a_question_the_member_cannot_see_is_not_found()
    {
        [$status] = $this->answer(3, 2, true);

        $this->assertSame(404, $status, 'A member cannot answer on a restricted tag\'s question');
        $this->assertSame(0, $this->database()->table('helpdesk_feedback')->count());
    }

    #[Test]
    public function a_guest_cannot_answer()
    {
        [$status] = $this->answer(1, null, true);

        $this->assertContains($status, [400, 401, 403]);
        $this->assertSame(0, $this->database()->table('helpdesk_feedback')->count());
    }

    #[Test]
    public function a_member_answers_changes_and_takes_back()
    {
        [$status, $body] = $this->answer(1, 2, true);
        $this->assertSame(200, $status);
        $this->assertSame(1, $body['data']['attributes']['helpdeskYes']);
        $this->assertTrue($body['data']['attributes']['helpdeskHelpful']);

        [, $body] = $this->answer(1, 3, true);
        $this->assertSame(2, $body['data']['attributes']['helpdeskYes']);

        [, $body] = $this->answer(1, 2, false);
        $this->assertSame(1, $body['data']['attributes']['helpdeskYes'], 'One row per member: a change replaces it');
        $this->assertFalse($body['data']['attributes']['helpdeskHelpful']);

        [, $body] = $this->answer(1, 2, null);
        $this->assertSame(1, $body['data']['attributes']['helpdeskYes']);
        $this->assertNull($body['data']['attributes']['helpdeskHelpful']);

        [, $body] = $this->call('GET', '/api/discussions/1');
        $this->assertSame(1, $body['data']['attributes']['helpdeskYes']);
        $this->assertNull($body['data']['attributes']['helpdeskHelpful'], 'A guest sees the count, never a member\'s answer');
    }
}
