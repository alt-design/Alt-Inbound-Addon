<?php

const BLOCKED_PAGE = '/alt-design/alt-inbound/blocked';

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
