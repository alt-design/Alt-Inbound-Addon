<?php

namespace AltDesign\AltInbound\Helpers;

class Ip
{
    private const IPV4_MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    /**
     * Return the standard written form of a single IP address, or null when the value is not one.
     *
     * IPv6 comes back lower case and compressed, and an IPv4 address written as IPv4-mapped IPv6 comes back as IPv4.
     */
    public static function canonical(?string $value): ?string
    {
        $value = trim((string) $value);

        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($value);

        if (strlen($packed) === 16 && str_starts_with($packed, self::IPV4_MAPPED_PREFIX)) {
            $packed = substr($packed, strlen(self::IPV4_MAPPED_PREFIX));
        }

        return inet_ntop($packed);
    }
}
