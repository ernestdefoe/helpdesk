<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * Users: 1 admin, 2 normal, 3 member.
 * Tags: 1 Printing (public), 2 Staff (restricted to admins).
 * Discussions (fof/best-answer decides "answered"):
 *   1 Printing, by 2, best answer picked (post 11)    → solved
 *   2 Printing, by 3, no best answer                  → open
 *   3 Staff,    by 1, best answer picked              → never shown to a guest
 *   4 Printing, by 2, hidden, best answer picked      → counted nowhere
 *   5 Printing, by 3, private                         → counted nowhere.
 */
abstract class HelpDeskTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'fof-best-answer', 'ernestdefoe-helpdesk');

        $now = Carbon::now();
        $d = fn (int $id, int $user, array $extra = []) => $extra + [
            'id' => $id, 'title' => "Question $id", 'slug' => "question-$id", 'created_at' => $now, 'last_posted_at' => $now,
            'user_id' => $user, 'first_post_id' => $id, 'comment_count' => 2, 'participant_count' => 1,
        ];
        $p = fn (int $id, int $discussion, int $number) => [
            'id' => $id, 'discussion_id' => $discussion, 'number' => $number, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>',
        ];

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'third', 'email' => 'third@machine.local', 'is_email_confirmed' => 1],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Printing', 'slug' => 'printing', 'position' => 0],
                ['id' => 2, 'name' => 'Staff', 'slug' => 'staff', 'position' => 1, 'is_restricted' => true],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag1.viewForum'],
                ['group_id' => Group::GUEST_ID, 'permission' => 'tag1.viewForum'],
            ],
            Discussion::class => [
                $d(1, 2, ['best_answer_post_id' => 11]),
                $d(2, 3),
                $d(3, 1, ['best_answer_post_id' => 13]),
                $d(4, 2, ['hidden_at' => $now, 'best_answer_post_id' => 14]),
                $d(5, 3, ['is_private' => true]),
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1], ['discussion_id' => 2, 'tag_id' => 1], ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 4, 'tag_id' => 1], ['discussion_id' => 5, 'tag_id' => 1],
            ],
            Post::class => [
                $p(1, 1, 1), $p(11, 1, 2), $p(2, 2, 1), $p(3, 3, 1), $p(13, 3, 2), $p(4, 4, 1), $p(14, 4, 2), $p(5, 5, 1),
            ],
        ]);
    }

    /** @return array{0: int, 1: mixed} */
    protected function call(string $method, string $path, ?int $actor = null, ?array $body = null): array
    {
        $options = ($actor ? ['authenticatedAs' => $actor] : []) + ($body !== null ? ['json' => $body] : []);
        $response = $this->send($this->request($method, $path, $options));

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }
}
