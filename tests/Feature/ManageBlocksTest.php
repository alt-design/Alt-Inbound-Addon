<?php

use AltDesign\AltInbound\Helpers\Data;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function addBlock(string $ip)
{
    return test()->postJson(cp_route('alt-inbound.create'), ['ip' => $ip]);
}

describe('adding an entry', function () {
    it('saves an IP address with an id made on the server', function () {
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.create'), ['ip' => '203.0.113.7', 'id' => 'chosen-by-the-client'])->assertOk();

        $entries = $this->entries();
        expect($entries)->toHaveCount(1)
            ->and($entries[0]['ip'])->toBe('203.0.113.7')
            ->and(Str::isUuid($entries[0]['id']))->toBeTrue();
    });

    it('saves an IP address in its standard form', function (string $ip, string $saved) {
        $this->signIn();

        addBlock($ip)->assertOk();

        expect($this->listedIps())->toBe([$saved])
            ->and(File::exists($this->sitePath().'/'.Data::blockPath($saved)))->toBeTrue();
    })->with([
        'IPv4 with whitespace around it' => ['  203.0.113.7 ', '203.0.113.7'],
        'IPv6 in upper case' => ['2001:DB8::1', '2001:db8::1'],
        'IPv6 written out in full' => ['2001:0db8:0000:0000:0000:0000:0000:0001', '2001:db8::1'],
        'IPv4-mapped IPv6' => ['::ffff:203.0.113.7', '203.0.113.7'],
    ]);

    it('keeps one entry when the same address is added in another form', function () {
        $this->signIn();

        addBlock('2001:db8::1');
        $id = $this->entries()[0]['id'];

        addBlock('2001:DB8:0:0:0:0:0:1')->assertOk();

        expect($this->entries())->toHaveCount(1)
            ->and($this->entries()[0]['id'])->toBe($id);
    });

    it('rejects anything that is not a single IP address', function (string $ip) {
        $this->signIn();

        addBlock($ip)->assertUnprocessable()->assertJsonValidationErrors('ip');

        expect($this->entries())->toBeEmpty();
    })->with([
        'an empty value' => '',
        'whitespace' => '   ',
        'a CIDR range' => '10.0.0.0/8',
        'a wildcard' => '192.168.1.*',
        'a long string' => str_repeat('a', 300),
        'a formula' => '=HYPERLINK("https://example.com")',
    ]);

    it('logs who added an entry, without the IP address', function () {
        Log::spy();
        $this->signIn();

        addBlock('203.0.113.7');

        Log::shouldHaveReceived('info')->once()->with('Alt Inbound: block added', [
            'user' => auth()->id(),
            'entry' => $this->entries()[0]['id'],
        ]);
    });
});

describe('removing an entry', function () {
    it('removes the entry with the posted id and IP address', function () {
        $this->signIn();
        $this->block('203.0.113.7', 'first');
        $this->block('198.51.100.20', 'second');

        $this->post(cp_route('alt-inbound.delete'), ['id' => 'first', 'ip' => '203.0.113.7'])->assertOk();

        expect($this->listedIps())->toBe(['198.51.100.20']);
    });

    it('removes an entry saved by an earlier version that is not a single IP address', function (string $ip) {
        $this->signIn();
        $this->block($ip, 'legacy');

        $this->post(cp_route('alt-inbound.delete'), ['id' => 'legacy', 'ip' => $ip])->assertOk();

        expect($this->entries())->toBeEmpty();
    })->with([
        'a wildcard' => '10.0.0.*',
        'trailing whitespace' => '203.0.113.7 ',
        'text whose file name contains a slash' => '???',
    ]);

    it('rejects an id or IP address that is not text', function (array $payload) {
        $this->signIn();
        $this->block('203.0.113.7');

        $this->postJson(cp_route('alt-inbound.delete'), $payload)->assertUnprocessable();

        expect($this->listedIps())->toBe(['203.0.113.7']);
    })->with([
        'a list as the IP address' => [['id' => 'test', 'ip' => ['203.0.113.7']]],
        'a list as the id' => [['id' => ['test'], 'ip' => '203.0.113.7']],
    ]);

    it('leaves the list alone when nothing matches', function () {
        $this->signIn();
        $this->block('203.0.113.7');

        $this->post(cp_route('alt-inbound.delete'), ['id' => 'missing', 'ip' => '198.51.100.20'])->assertOk();

        expect($this->listedIps())->toBe(['203.0.113.7']);
    });

    it('logs who removed an entry, without the IP address', function () {
        Log::spy();
        $this->signIn();
        $this->block('203.0.113.7', 'first');

        $this->post(cp_route('alt-inbound.delete'), ['id' => 'first', 'ip' => '203.0.113.7']);

        Log::shouldHaveReceived('info')->once()->with('Alt Inbound: block removed', [
            'user' => auth()->id(),
            'entry' => 'first',
        ]);
    });
});

it('lists entries even when one of the files cannot be read', function () {
    $this->publishAssets();
    $this->signIn();
    $this->block('203.0.113.7');
    File::put($this->sitePath().'/'.Data::BLOCKS_DIRECTORY.'/broken.yaml', "ip: [unclosed\n");
    File::put($this->sitePath().'/'.Data::BLOCKS_DIRECTORY.'/empty.yaml', '');

    expect($this->listedIps())->toBe(['203.0.113.7']);
    $this->get(cp_route('alt-inbound.index'))->assertOk();
});
