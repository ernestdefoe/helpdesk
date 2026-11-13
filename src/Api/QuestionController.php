<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Api;

use ErnestDefoe\HelpDesk\Feedback;
use ErnestDefoe\HelpDesk\Kb;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The question page's side column, in one request:
 *
 *   GET  /api/helpdesk/discussions/{id}           related articles + the "solved it" numbers
 *   POST /api/helpdesk/discussions/{id}/feedback  {helpful: true|false|null}
 *
 * Asked only by visitors on Help Desk, and only once per question page.
 * A question the visitor cannot see is a 404, exactly as core answers it.
 */
class QuestionController implements RequestHandlerInterface
{
    public function __construct(protected Kb $kb, protected Feedback $feedback)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $id = (int) Arr::get($request->getQueryParams(), 'id', $request->getAttribute('routeParameters')['id'] ?? 0);

        /** @var Discussion $discussion */
        $discussion = Discussion::query()->whereVisibleTo($actor)->findOrFail($id);
        $asks = $this->feedback->asks($discussion);

        if ($request->getMethod() === 'POST') {
            $actor->assertRegistered();

            if (! $asks) {
                throw new PermissionDeniedException();
            }

            $value = Arr::get((array) $request->getParsedBody(), 'helpful');
            $this->feedback->record($discussion, $actor, $value === null ? null : (bool) $value);
        }

        return new JsonResponse([
            'kb' => $request->getMethod() === 'POST' ? null : $this->kb->related($discussion, $actor),
            'feedback' => $asks ? $this->feedback->read($discussion, $actor) : null,
        ]);
    }
}
