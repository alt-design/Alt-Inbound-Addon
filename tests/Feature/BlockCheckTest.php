<?php

use AltDesign\AltInbound\Helpers\Data;

dataset('pages', [
    'a page' => '/about',
    'a page with the asset path in its query string' => '/about?ref=vendor/alt-inbound',
    'a page with the asset path further down its path' => '/about/vendor/alt-inbound',
    'a page under the asset path' => '/vendor/alt-inbound/about',
]);

describe('block list', function () {
    it('sends a blocked visitor to the blocked page', function (string $uri) {
        $this->block('203.0.113.7');

        $this->visitAs('203.0.113.7', $uri)->assertRedirect(BLOCKED_PAGE);
    })->with('pages');

    it('lets any other visitor through', function (string $uri) {
        $this->block('203.0.113.7');

        $this->visitAs('198.51.100.20', $uri)->assertOk()->assertSee('page');
    })->with('pages');

    it('shows a blocked visitor the blocked page rather than redirecting them to it again', function () {
        $this->block('203.0.113.7');

        expect($this->visitAs('203.0.113.7', BLOCKED_PAGE)->isRedirect())->toBeFalse();
    });

    it('sends a visitor who is not blocked away from the blocked page', function () {
        $this->block('203.0.113.7');

        $this->visitAs('198.51.100.20', BLOCKED_PAGE)->assertRedirect('/');
    });
});

describe('allow list', function () {
    it('sends a visitor who is not on the list to the blocked page', function (string $uri) {
        $this->useAllowList();
        $this->block('203.0.113.7');

        $this->visitAs('198.51.100.20', $uri)->assertRedirect(BLOCKED_PAGE);
    })->with('pages');

    it('lets a visitor on the list through', function (string $uri) {
        $this->useAllowList();
        $this->block('203.0.113.7');

        $this->visitAs('203.0.113.7', $uri)->assertOk()->assertSee('page');
    })->with('pages');

    it('shows a visitor who is not on the list the blocked page rather than redirecting them to it again', function () {
        $this->useAllowList();
        $this->block('203.0.113.7');

        expect($this->visitAs('198.51.100.20', BLOCKED_PAGE)->isRedirect())->toBeFalse();
    });

    it('sends a visitor on the list away from the blocked page', function () {
        $this->useAllowList();
        $this->block('203.0.113.7');

        $this->visitAs('203.0.113.7', BLOCKED_PAGE)->assertRedirect('/');
    });
});

describe('matching', function () {
    it('keeps matching an IPv4 entry saved by an earlier version', function () {
        $this->block('203.0.113.7', '6650f1c2a8b3d');

        $this->visitAs('203.0.113.7', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('keeps matching an entry saved by an earlier version in exactly the form the visitor arrives in', function () {
        $this->block('::ffff:203.0.113.7');

        $this->visitAs('::ffff:203.0.113.7', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('matches an IPv6 visitor however the entry was written', function (string $entry) {
        (new Data('Inbound'))->add($entry);

        $this->visitAs('2001:db8::1', '/about')->assertRedirect(BLOCKED_PAGE);
    })->with([
        'upper case' => '2001:DB8::1',
        'written out in full' => '2001:0db8:0000:0000:0000:0000:0000:0001',
    ]);

    it('matches a visitor whose IPv6 address arrives written another way', function () {
        (new Data('Inbound'))->add('2001:db8::1');

        $this->visitAs('2001:DB8:0:0:0:0:0:1', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('matches a visitor whose IPv4 address arrives as IPv4-mapped IPv6', function () {
        (new Data('Inbound'))->add('203.0.113.7');

        $this->visitAs('::ffff:203.0.113.7', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('lets a visitor with no IP address through in block-list mode', function () {
        $this->block('203.0.113.7');

        $this->visitAs('', '/about')->assertOk()->assertSee('page');
    });

    it('sends a visitor with no IP address to the blocked page in allow-list mode', function () {
        $this->useAllowList();
        $this->block('203.0.113.7');

        $this->visitAs('', '/about')->assertRedirect(BLOCKED_PAGE);
    });
});
