<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Tests\integration\api;

use ErnestDefoe\HelpDesk\Tests\integration\HelpDeskTestCase;
use ErnestDefoe\HelpDesk\Topics;
use Flarum\User\Guest;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TopicsTest extends HelpDeskTestCase
{
    #[Test]
    public function counts_come_from_best_answer_and_skip_hidden_and_private()
    {
        $topics = $this->app()->getContainer()->make(Topics::class);

        $this->assertSame('best', $topics->mode());
        $this->assertSame([1 => [2, 1], 2 => [1, 1]], $topics->all('best'));
    }

    #[Test]
    public function a_restricted_tag_is_handed_only_to_who_can_see_it()
    {
        $topics = $this->app()->getContainer()->make(Topics::class);

        $this->assertSame([1 => [2, 1]], $topics->for(new Guest()));
        $this->assertSame([1 => [2, 1], 2 => [1, 1]], $topics->for(User::find(1)));
    }

    #[Test]
    public function the_page_carries_the_numbers()
    {
        $response = $this->send($this->request('GET', '/'));
        $html = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data-helpdesk-fonts="on"', $html);
        $this->assertStringContainsString('"helpdesk":{"mode":"best","topics":{"1":[2,1]}', $html);
        $this->assertStringNotContainsString('"2":[1,1]', $html, 'The Staff tag\'s numbers never reach a guest');
    }
}
