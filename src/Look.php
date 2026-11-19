<?php

/*
 * This file is part of ernestdefoe/helpdesk.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\HelpDesk;

/**
 * The two colours an admin can set. Kept here, in one place, because a value
 * has to be checked before it reaches the stylesheet or the page head: an
 * empty or malformed one falls back to the theme's own colour.
 */
class Look
{
    public const ACCENT = '#0E6E6E';

    public const BAR = '#0F2A33';

    public static function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : $fallback;
    }
}
