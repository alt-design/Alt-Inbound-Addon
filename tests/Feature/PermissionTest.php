<?php

use AltDesign\AltInbound\Helpers\Data;
use Illuminate\Http\UploadedFile;

it('turns away a control panel user without the Alt Inbound permission', function (string $method, string $route, array $payload) {
    $this->signIn(permissions: []);
    $this->block('203.0.113.7');

    $this->{$method}(cp_route($route), $payload)->assertRedirect(cp_route('index'));

    expect($this->listedIps())->toBe(['203.0.113.7'])
        ->and(file_exists($this->sitePath().'/content/alt-inbound/whitelist.yaml'))->toBeFalse()
        ->and(file_exists($this->sitePath().'/content/alt-inbound/custom-view.yaml'))->toBeFalse();
})->with([
    'list' => ['get', 'alt-inbound.index', []],
    'export' => ['get', 'alt-inbound.export', []],
    'add' => ['post', 'alt-inbound.create', ['ip' => '198.51.100.20']],
    'remove' => ['post', 'alt-inbound.delete', ['id' => 'test', 'ip' => '203.0.113.7']],
    'import' => ['post', 'alt-inbound.import', fn () => ['file' => UploadedFile::fake()->createWithContent('blocks.csv', "ip\n198.51.100.20\n")]],
    'switch mode' => ['post', 'alt-inbound.blacklist', ['blacklist' => false]],
    'custom view' => ['post', 'alt-inbound.custom-view', ['custom-view' => 'templates.blocked']],
]);

it('lets a control panel user with the Alt Inbound permission make changes', function () {
    $this->signIn(permissions: ['view alt-inbound']);

    $this->postJson(cp_route('alt-inbound.create'), ['ip' => '198.51.100.20'])->assertOk();

    expect($this->listedIps())->toBe(['198.51.100.20']);
});

it('registers the settings permission under the Alt Inbound permission', function () {
    $parent = \Statamic\Facades\Permission::get('view alt-inbound');

    expect($parent->children()->map->value()->all())->toBe(['manage alt-inbound settings']);
});

it('keeps the settings from a user who can manage the list but not the settings', function (string $route, array $payload) {
    $this->signIn(permissions: ['view alt-inbound']);
    $this->useCustomView('templates.blocked');

    $this->postJson(cp_route($route), $payload)->assertForbidden();
    $this->post(cp_route($route), $payload)->assertRedirect(cp_route('index'));

    expect(Data::whiteListing())->toBe(0)
        ->and(Data::customView())->toBe('templates.blocked');
})->with([
    'switch mode' => ['alt-inbound.blacklist', ['blacklist' => false]],
    'custom view' => ['alt-inbound.custom-view', ['custom-view' => '']],
]);

it('lets a user who can manage the list but not the settings still change the list', function () {
    $this->signIn(permissions: ['view alt-inbound']);
    $this->block('203.0.113.7');

    $this->post(cp_route('alt-inbound.delete'), ['id' => 'test', 'ip' => '203.0.113.7']);
    $this->post(cp_route('alt-inbound.import'), ['file' => UploadedFile::fake()->createWithContent('blocks.csv', "ip\n198.51.100.20\n")]);
    $this->get(cp_route('alt-inbound.export'))->assertOk();

    expect($this->listedIps())->toBe(['198.51.100.20']);
});

it('lets a user with the settings permission change the settings', function () {
    $this->signIn(permissions: ['view alt-inbound', 'manage alt-inbound settings']);

    $this->postJson(cp_route('alt-inbound.blacklist'), ['blacklist' => false])->assertOk();
    $this->postJson(cp_route('alt-inbound.custom-view'), ['custom-view' => 'templates.blocked'])->assertOk();

    expect(Data::whiteListing())->toBe(1)
        ->and(Data::customView())->toBe('templates.blocked');
});

it('turns away a user with the settings permission but not the Alt Inbound one', function () {
    $this->signIn(permissions: ['manage alt-inbound settings']);

    $this->post(cp_route('alt-inbound.blacklist'), ['blacklist' => false])->assertRedirect(cp_route('index'));

    expect(Data::whiteListing())->toBe(0);
});

it('tells the control panel page whether the user can change the settings, and shows everyone the mode', function (?array $permissions, bool $canManageSettings) {
    $this->useAllowList();
    $this->signIn(permissions: $permissions);

    expect($this->indexProps())
        ->canManageSettings->toBe($canManageSettings)
        ->blacklist->toBeFalse();
})->with([
    'list only' => [['view alt-inbound'], false],
    'list and settings' => [['view alt-inbound', 'manage alt-inbound settings'], true],
    'super user' => [null, true],
]);
