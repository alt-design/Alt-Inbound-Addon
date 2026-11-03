<?php

use AltDesign\AltInbound\Helpers\BlockedView;
use AltDesign\AltInbound\Helpers\Data;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ViewException;

describe('switching mode', function () {
    it('switches between block-list and allow-list mode', function () {
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.blacklist'), ['blacklist' => false])->assertOk();
        expect(Data::whiteListing())->toBe(1);

        $this->postJson(cp_route('alt-inbound.blacklist'), ['blacklist' => true])->assertOk();
        expect(Data::whiteListing())->toBe(0);
    });

    it('accepts the form values the 1.x control panel sends', function () {
        $this->signIn();

        $this->post(cp_route('alt-inbound.blacklist'), ['blacklist' => '0']);
        expect(Data::whiteListing())->toBe(1);

        $this->post(cp_route('alt-inbound.blacklist'), ['blacklist' => '1']);
        expect(Data::whiteListing())->toBe(0);
    });

    it('leaves the mode alone when no valid value is sent', function (array $payload) {
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.blacklist'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('blacklist');

        expect(Data::whiteListing())->toBe(0);
    })->with([
        'no value' => [[]],
        'an empty value' => [['blacklist' => '']],
        'text' => [['blacklist' => 'allow']],
    ]);

    it('logs who changed the mode', function () {
        Log::spy();
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.blacklist'), ['blacklist' => false]);

        Log::shouldHaveReceived('info')->once()->with('Alt Inbound: mode changed', [
            'user' => auth()->id(),
            'mode' => 'allow list',
        ]);
    });
});

describe('custom blocked page', function () {
    it('saves one of the site\'s own views', function (string $name) {
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.custom-view'), ['custom-view' => $name])->assertOk();

        expect(Data::customView())->toBe($name);
    })->with([
        'dot notation' => 'templates.blocked',
        'slash notation' => 'templates/blocked',
    ]);

    it('clears the view when the field is emptied', function () {
        $this->signIn();
        $this->useCustomView('templates.blocked');

        $this->postJson(cp_route('alt-inbound.custom-view'), ['custom-view' => ''])->assertOk();

        expect(Data::customView())->toBeNull();
    });

    it('rejects a view that is not one of the site\'s own', function (string $name) {
        $this->signIn();
        $this->useCustomView('templates.blocked');

        $this->postJson(cp_route('alt-inbound.custom-view'), ['custom-view' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('custom-view');

        expect(Data::customView())->toBe('templates.blocked');
    })->with([
        'a view that does not exist' => 'templates.missing',
        'a namespaced view' => 'statamic::layout',
        'a parent directory' => '../views/templates/blocked',
        'a double dot' => 'templates..blocked',
        'an absolute path' => '/templates/blocked',
        'a long name' => str_repeat('a', 101),
    ]);

    it('does not accept a view name with a line break after it', function () {
        expect(BlockedView::isAllowed("templates.blocked\n"))->toBeFalse()
            ->and(BlockedView::isAllowed('templates.blocked'))->toBeTrue();
    });

    it('logs who changed the view', function () {
        Log::spy();
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.custom-view'), ['custom-view' => 'templates.blocked']);

        Log::shouldHaveReceived('info')->once()->with('Alt Inbound: blocked page view changed', [
            'user' => auth()->id(),
            'view' => 'templates.blocked',
        ]);
    });

    it('shows a blocked visitor the custom view', function () {
        $this->block('203.0.113.7');
        $this->useCustomView('templates.blocked');

        $this->visitAs('203.0.113.7', BLOCKED_PAGE)->assertOk()->assertSee('Custom blocked page');
    });

    it('shows the default page when the stored view cannot be used', function (string $name) {
        $this->publishAssets();
        $this->block('203.0.113.7');
        $this->useCustomView($name);

        $this->visitAs('203.0.113.7', BLOCKED_PAGE)->assertOk()->assertSee('Oops, sorry about this');
    })->with([
        'a view that no longer exists' => 'templates.missing',
        'a namespaced view saved by an earlier version' => 'statamic::layout',
    ]);

    it('shows the default page and reports the error when the custom view fails to render', function () {
        Exceptions::fake();
        $this->publishAssets();
        $this->block('203.0.113.7');
        $this->useCustomView('templates.broken');

        $this->visitAs('203.0.113.7', BLOCKED_PAGE)->assertOk()->assertSee('Oops, sorry about this');

        Exceptions::assertReported(fn (ViewException $e) => str_contains($e->getMessage(), 'This view cannot render.'));
    });
});
