<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Binds one of Help Desk's integration points to an implementation.
 *
 * Used inside Extend\Conditional, so the implementation for another
 * extension is only bound (and its classes only loaded) when that extension
 * is enabled. A later binding wins, which is how Answers takes precedence over
 * Best Answer when a forum runs both.
 */
class Bind implements ExtenderInterface
{
    /**
     * @param class-string $abstract
     * @param class-string $concrete
     */
    public function __construct(private string $abstract, private string $concrete)
    {
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->bind($this->abstract, $this->concrete);
    }
}
