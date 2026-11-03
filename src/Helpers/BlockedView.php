<?php

namespace AltDesign\AltInbound\Helpers;

use Illuminate\Support\Facades\View;

class BlockedView
{
    public const DEFAULT = 'alt-inbound::block';

    public const MAX_LENGTH = 100;

    private const NAME_PATTERN = '/^[A-Za-z0-9_-]+(?:[.\/][A-Za-z0-9_-]+)*\z/';

    /**
     * Whether a view can be used as the blocked page: one of the site's own views, named without a namespace, that exists.
     */
    public static function isAllowed(?string $name): bool
    {
        return $name !== null
            && strlen($name) <= self::MAX_LENGTH
            && preg_match(self::NAME_PATTERN, $name) === 1
            && View::exists($name);
    }
}
