<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Api;

use ErnestDefoe\HelpDesk\ActiveTheme;
use ErnestDefoe\HelpDesk\Feedback;
use ErnestDefoe\HelpDesk\Guides\Guides;
use Flarum\Api\Context;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;

/**
 * The question page's side column, as fields of the discussion itself:
 *
 *   helpdeskYes      how many people said the accepted answer solved it
 *   helpdeskHelpful  this member's own answer (true, false or null); written
 *                    with PATCH /api/discussions/{id}, like flarum/subscriptions'
 *                    "subscription", and only on a solved question
 *   helpdeskGuides   up to three related knowledge base articles
 *
 * Only on the discussion's own page (Show, and the Update that answers it),
 * never in lists, and only for visitors on Help Desk: each costs one small
 * query for one discussion.
 */
class DiscussionFields
{
    public function __construct(
        protected Feedback $feedback,
        protected Guides $guides,
        protected ActiveTheme $theme,
    ) {
    }

    /** @return array<Schema\Attribute> */
    public function __invoke(): array
    {
        return [
            Schema\Integer::make('helpdeskYes')
                ->visible(fn (Discussion $discussion, Context $context) => $this->asks($discussion, $context))
                ->get(fn (Discussion $discussion) => $this->feedback->yes($discussion)),

            Schema\Boolean::make('helpdeskHelpful')
                ->nullable()
                ->visible(fn (Discussion $discussion, Context $context) => $this->asks($discussion, $context))
                ->writable(fn (Discussion $discussion, Context $context) => $context->updating(DiscussionResource::class)
                    && ! $context->getActor()->isGuest()
                    && $this->feedback->asks($discussion))
                ->get(fn (Discussion $discussion, Context $context) => $this->feedback->mine($discussion, $context->getActor()))
                // Stored in Help Desk's own table once the discussion is saved,
                // never as a column of the discussion.
                ->set(fn () => null)
                ->save(fn (Discussion $discussion, ?bool $helpful, Context $context) => $this->feedback->record($discussion, $context->getActor(), $helpful)),

            Schema\Arr::make('helpdeskGuides')
                ->visible(fn (Discussion $discussion, Context $context) => $context->showing(DiscussionResource::class) && $this->theme->for($context->request))
                ->get(fn (Discussion $discussion, Context $context) => $this->guides->related($discussion, $context->getActor())),
        ];
    }

    /** On the question's own page, for a Help Desk visitor, under an accepted answer. */
    protected function asks(Discussion $discussion, Context $context): bool
    {
        return ($context->showing(DiscussionResource::class) || $context->updating(DiscussionResource::class))
            && $this->theme->for($context->request)
            && $this->feedback->asks($discussion);
    }
}
