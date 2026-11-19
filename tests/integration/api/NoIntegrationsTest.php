<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Tests\integration\api;

use ErnestDefoe\HelpDesk\ActiveTheme;
use ErnestDefoe\HelpDesk\Answered\Answered;
use ErnestDefoe\HelpDesk\Answered\Nothing;
use ErnestDefoe\HelpDesk\Guides\Guides;
use ErnestDefoe\HelpDesk\Guides\NoGuides;
use ErnestDefoe\HelpDesk\NoTopics;
use ErnestDefoe\HelpDesk\TopicNumbers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/** Help Desk alone: no Tags, Answers, Best Answer, Knowledge Base or Wardrobe. */
class NoIntegrationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('ernestdefoe-helpdesk');
    }

    #[Test]
    public function the_defaults_are_bound_and_the_page_renders()
    {
        $container = $this->app()->getContainer();

        $this->assertInstanceOf(Nothing::class, $container->make(Answered::class));
        $this->assertInstanceOf(NoTopics::class, $container->make(TopicNumbers::class));
        $this->assertInstanceOf(NoGuides::class, $container->make(Guides::class));
        $this->assertSame(ActiveTheme::class, get_class($container->make(ActiveTheme::class)));

        $response = $this->send($this->request('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"helpdesk":{"mode":"none","topics":{},"guides":null}', (string) $response->getBody());
    }
}
