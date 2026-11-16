<?php

use AltDesign\AltInbound\Helpers\Data;
use AltDesign\AltInbound\Helpers\Mode;
use Illuminate\Support\Facades\File;

describe('ALT_INBOUND_MODE', function () {
    it('sends every visitor to the blocked page in allow-list mode when the content folder is missing', function () {
        config(['alt-inbound.mode' => 'allow']);

        expect(File::exists($this->sitePath().'/content/alt-inbound'))->toBeFalse();

        $this->visitAs('198.51.100.20', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('lets a visitor on the list through in allow-list mode without the control panel setting', function () {
        config(['alt-inbound.mode' => 'allow']);
        $this->block('203.0.113.7');

        $this->visitAs('203.0.113.7', '/about')->assertOk()->assertSee('page');
        $this->visitAs('198.51.100.20', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('overrides allow-list mode set in the control panel', function () {
        config(['alt-inbound.mode' => 'block']);
        $this->useAllowList();
        $this->block('203.0.113.7');

        $this->visitAs('198.51.100.20', '/about')->assertOk()->assertSee('page');
        $this->visitAs('203.0.113.7', '/about')->assertRedirect(BLOCKED_PAGE);
    });

    it('reads the value without regard to case or surrounding space', function (string $value, int $allowListing) {
        config(['alt-inbound.mode' => $value]);

        expect(Data::whiteListing())->toBe($allowListing)
            ->and(Mode::unrecognisedValue())->toBeNull();
    })->with([
        'Allow' => ['Allow', 1],
        'ALLOW' => ['ALLOW', 1],
        'allow with spaces' => [' allow ', 1],
        'Block' => ['Block', 0],
    ]);

    it('uses the control panel setting when the value is empty', function (mixed $value) {
        config(['alt-inbound.mode' => $value]);

        expect(Mode::configured())->toBeNull()
            ->and(Mode::unrecognisedValue())->toBeNull()
            ->and(Data::whiteListing())->toBe(0);

        $this->useAllowList();

        expect(Data::whiteListing())->toBe(1);
    })->with([
        'not set' => [null],
        'empty' => [''],
        'spaces' => ['  '],
        'false' => [false],
    ]);

    it('treats any other value as allow-list mode, so a mistyped value does not let every visitor in', function (mixed $value) {
        config(['alt-inbound.mode' => $value]);

        expect(Data::whiteListing())->toBe(1)
            ->and(Mode::unrecognisedValue())->not->toBeNull();

        $this->visitAs('198.51.100.20', '/about')->assertRedirect(BLOCKED_PAGE);
    })->with([
        'whitelist' => ['whitelist'],
        'blacklist' => ['blacklist'],
        'true' => [true],
        'a number' => [1],
    ]);

    it('still lets a control panel user in when every visitor is blocked', function () {
        config(['alt-inbound.mode' => 'allow']);
        $this->signIn();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->get(cp_route('alt-inbound.index'))
            ->assertOk();
    });
});

describe('switching mode while ALT_INBOUND_MODE is set', function () {
    it('refuses the change and leaves the content file alone', function (string $mode, bool $blacklist, bool $request) {
        config(['alt-inbound.mode' => $mode]);
        $blacklist && $this->useAllowList();
        $this->signIn();

        $response = $request
            ? $this->postJson(cp_route('alt-inbound.blacklist'), ['blacklist' => $blacklist])
            : $this->post(cp_route('alt-inbound.blacklist'), ['blacklist' => $blacklist ? '1' : '0']);

        $request
            ? $response->assertUnprocessable()->assertJsonValidationErrors('blacklist')
            : $response->assertSessionHasErrors('blacklist');

        expect(File::exists($this->sitePath().'/content/alt-inbound/whitelist.yaml'))->toBe($blacklist);
    })->with([
        'allow' => 'allow',
        'block' => 'block',
    ])->with([
        'to block list' => true,
        'to allow list' => false,
    ])->with([
        'as JSON' => true,
        'as a form' => false,
    ]);
});

describe('control panel', function () {
    it('says whether the mode is set by the environment', function (?string $value, bool $setByEnvironment, bool $blacklist) {
        config(['alt-inbound.mode' => $value]);
        $this->signIn();

        expect($this->indexProps())
            ->modeSetByEnvironment->toBe($setByEnvironment)
            ->blacklist->toBe($blacklist);
    })->with([
        'not set' => [null, false, true],
        'allow' => ['allow', true, false],
        'block' => ['block', true, true],
    ]);

    it('warns that every visitor is blocked when the allow list is empty', function () {
        config(['alt-inbound.mode' => 'allow']);
        $this->signIn();

        expect($this->indexProps()['warnings'])->toBe([
            'Allow-list mode is on and the list is empty, so every visitor to the site is sent to the blocked page.',
        ]);
    });

    it('warns that allow-list mode saved in the content folder can be lost on deployment', function () {
        $this->useAllowList();
        $this->block('203.0.113.7');
        $this->signIn();

        expect($this->indexProps()['warnings'])->toHaveCount(1)
            ->and($this->indexProps()['warnings'][0])->toContain('Set ALT_INBOUND_MODE=allow');
    });

    it('names a value it does not recognise', function () {
        config(['alt-inbound.mode' => 'whitelist']);
        $this->block('203.0.113.7');
        $this->signIn();

        expect($this->indexProps()['warnings'])->toBe([
            'ALT_INBOUND_MODE is set to "whitelist", which is neither allow nor block, so the site is in allow-list mode.',
        ]);
    });

    it('gives no warnings when there is nothing to warn about', function (?string $value, bool $useAllowList) {
        config(['alt-inbound.mode' => $value]);
        $useAllowList && $this->useAllowList();
        $this->block('203.0.113.7');
        $this->signIn();

        expect($this->indexProps()['warnings'])->toBe([]);
    })->with([
        'block-list mode from the control panel' => [null, false],
        'block-list mode from the environment' => ['block', true],
        'allow-list mode from the environment with a list' => ['allow', false],
    ]);

    it('gives no warning about an empty list in block-list mode', function () {
        $this->signIn();

        expect($this->indexProps()['warnings'])->toBe([]);
    });
});
