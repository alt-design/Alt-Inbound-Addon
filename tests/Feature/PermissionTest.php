<?php

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
