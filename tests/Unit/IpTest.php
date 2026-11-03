<?php

use AltDesign\AltInbound\Helpers\Ip;

it('writes an IP address in its standard form', function (string $value, string $expected) {
    expect(Ip::canonical($value))->toBe($expected);
})->with([
    'IPv4' => ['203.0.113.7', '203.0.113.7'],
    'IPv4 with whitespace around it' => [" 203.0.113.7 \n", '203.0.113.7'],
    'IPv6 in upper case' => ['2001:DB8::1', '2001:db8::1'],
    'IPv6 written out in full' => ['2001:0db8:0000:0000:0000:0000:0000:0001', '2001:db8::1'],
    'IPv6 already in standard form' => ['2001:db8::1', '2001:db8::1'],
    'IPv4-mapped IPv6' => ['::ffff:203.0.113.7', '203.0.113.7'],
    'IPv4-mapped IPv6 written in hex' => ['::FFFF:cb00:7107', '203.0.113.7'],
]);

it('rejects anything that is not a single IP address', function (?string $value) {
    expect(Ip::canonical($value))->toBeNull();
})->with([
    'nothing' => [null],
    'an empty string' => [''],
    'whitespace' => ['   '],
    'a CIDR range' => ['10.0.0.0/8'],
    'a wildcard' => ['10.0.0.*'],
    'a hyphenated range' => ['10.0.0.1-10.0.0.9'],
    'an IPv6 zone' => ['fe80::1%eth0'],
    'IPv4 with leading zeros' => ['010.0.0.1'],
    'an out of range octet' => ['256.1.1.1'],
    'a bracketed IPv6' => ['[2001:db8::1]'],
    'a hostname' => ['example.com'],
    'a formula' => ['=1+1'],
]);
