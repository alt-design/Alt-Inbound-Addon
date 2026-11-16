<?php

namespace AltDesign\AltInbound\Helpers;

class Mode
{
    public const ALLOW = 'allow';

    public const BLOCK = 'block';

    /**
     * The mode set by ALT_INBOUND_MODE, or null when it is not set and the control panel setting applies.
     *
     * Any value other than `allow` or `block` counts as `allow`, so a mistyped value sends visitors to the blocked page
     * rather than letting every one of them in.
     *
     * @return 'allow'|'block'|null
     */
    public static function configured(): ?string
    {
        $value = self::configuredText();

        if ($value === '') {
            return null;
        }

        return $value === self::BLOCK ? self::BLOCK : self::ALLOW;
    }

    /**
     * The ALT_INBOUND_MODE value when it is set to something other than `allow` or `block`.
     */
    public static function unrecognisedValue(): ?string
    {
        $value = self::configuredText();

        return in_array($value, ['', self::ALLOW, self::BLOCK], true) ? null : $value;
    }

    /**
     * Warnings for the control panel about the mode and the list.
     *
     * @return list<string>
     */
    public static function warnings(bool $allowListing, int $entries): array
    {
        $warnings = [];

        if (($value = self::unrecognisedValue()) !== null) {
            $warnings[] = "ALT_INBOUND_MODE is set to \"{$value}\", which is neither allow nor block, so the site is in allow-list mode.";
        }

        if ($allowListing && $entries === 0) {
            $warnings[] = 'Allow-list mode is on and the list is empty, so every visitor to the site is sent to the blocked page.';
        }

        if ($allowListing && self::configured() === null) {
            $warnings[] = 'Allow-list mode is saved in content/alt-inbound. If that folder is not kept between deployments, the site goes back to block-list mode and lets every visitor in. Set ALT_INBOUND_MODE=allow in the site\'s .env to keep it in allow-list mode.';
        }

        return $warnings;
    }

    private static function configuredText(): string
    {
        $value = config('alt-inbound.mode');

        if (! is_scalar($value) || $value === false) {
            return '';
        }

        return strtolower(trim((string) $value));
    }
}
