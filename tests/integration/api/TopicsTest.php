<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Tests\integration\api;

use ErnestDefoe\HelpDesk\Answered\Answered;
use ErnestDefoe\HelpDesk\Answered\BestAnswer;
use ErnestDefoe\HelpDesk\Tests\integration\HelpDeskTestCase;
use ErnestDefoe\HelpDesk\TopicNumbers;
use ErnestDefoe\HelpDesk\Topics;
use Flarum\User\Guest;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TopicsTest extends HelpDeskTestCase
{
    #[Test]
    public function counts_come_from_best_answer_and_skip_hidden_and_private()
    {
        $container = $this->app()->getContainer();

        $this->assertInstanceOf(BestAnswer::class, $container->make(Answered::class), 'Best Answer is bound when it is enabled');
        $this->assertInstanceOf(Topics::class, $container->make(TopicNumbers::class), 'Tags are on');
        $this->assertSame([1 => [2, 1]], $container->make(Topics::class)->all(), 'Discussion 3 is also in the restricted Staff tag, so it counts nowhere');
    }

    #[Test]
    public function restricted_tags_are_never_counted()
    {
        $topics = $this->app()->getContainer()->make(TopicNumbers::class);

        $this->assertSame([1 => [2, 1]], $topics->for(new Guest()));
        $this->assertSame([1 => [2, 1]], $topics->for(User::find(1)));
    }

    #[Test]
    public function the_page_carries_the_numbers()
    {
        $response = $this->send($this->request('GET', '/'));
        $html = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data-helpdesk-fonts="on"', $html);
        $this->assertStringContainsString('"helpdesk":{"mode":"best","topics":{"1":[2,1]}', $html);
        $this->assertStringNotContainsString('"2":[', $html, 'The Staff tag\'s numbers never reach a guest');
    }
}
