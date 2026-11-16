<?php

use AltDesign\AltInbound\Helpers\Data;
use Illuminate\Support\Facades\File;

function problems(): array
{
    return collect((new Data('Inbound'))->allWithProblems())->mapWithKeys(fn ($entry) => [$entry['ip'] => $entry['problem']])->all();
}

it('flags an entry saved by an earlier version that is not a single IP address', function (string $ip) {
    $this->blockAsEarlierVersion($ip);

    expect(problems())->toBe([$ip => 'invalid']);
})->with([
    'a range' => '10.0.0.0/8',
    'a wildcard' => '10.0.0.*',
    'text' => 'hello',
    'empty' => '',
]);

it('flags an address an earlier version saved in a form other than the standard one', function (string $ip) {
    $this->blockAsEarlierVersion($ip);

    expect(problems())->toBe([$ip => 'non-standard']);
})->with([
    'a space in front' => ' 203.0.113.7',
    'upper case IPv6' => '2001:DB8::1',
    'IPv4-mapped IPv6' => '::ffff:203.0.113.7',
]);

it('does not flag an address in standard form', function (string $ip) {
    $this->blockAsEarlierVersion($ip);
    (new Data('Inbound'))->add('198.51.100.20');

    expect(problems())->toEqual([$ip => null, '198.51.100.20' => null]);
})->with([
    'IPv4' => '203.0.113.7',
    'IPv6' => '2001:db8::1',
]);

it('flags an entry with no id', function () {
    $this->blockAsEarlierVersion('10.0.0.*', id: null);

    expect((new Data('Inbound'))->allWithProblems())->toBe([['ip' => '10.0.0.*', 'id' => null, 'problem' => 'invalid']]);
});

it('matches visitors the way the flags say', function () {
    $this->blockAsEarlierVersion('10.0.0.*');
    $this->blockAsEarlierVersion(' 203.0.113.7');
    $this->blockAsEarlierVersion('::ffff:198.51.100.20');

    $this->visitAs('10.0.0.1', '/about')->assertOk();
    $this->visitAs('203.0.113.7', '/about')->assertOk();
    $this->visitAs('198.51.100.20', '/about')->assertOk();
    $this->visitAs('::ffff:198.51.100.20', '/about')->assertRedirect(BLOCKED_PAGE);
});

it('lists flagged entries first in the control panel', function () {
    $this->signIn();

    foreach (range(1, 12) as $i) {
        (new Data('Inbound'))->add("203.0.113.{$i}");
    }

    $this->blockAsEarlierVersion('10.0.0.*', 'old');
    touch($this->sitePath().'/'.Data::blockPath('10.0.0.*'), now()->subYear()->timestamp);

    $items = $this->indexProps()['items'];

    expect($items)->toHaveCount(13)
        ->and($items[0])->toBe(['ip' => '10.0.0.*', 'id' => 'old', 'problem' => 'invalid'])
        ->and(collect($items)->skip(1)->pluck('problem')->filter()->all())->toBe([]);
});

it('removes a flagged entry, and adding the address again saves it unflagged', function () {
    $this->signIn();
    $this->blockAsEarlierVersion(' 203.0.113.7', 'old');

    $this->post(cp_route('alt-inbound.delete'), ['id' => 'old', 'ip' => '203.0.113.7']);
    $this->postJson(cp_route('alt-inbound.create'), ['ip' => '203.0.113.7'])
        ->assertOk()
        ->assertJsonPath('data.0.ip', '203.0.113.7')
        ->assertJsonPath('data.0.problem', null);

    expect(problems())->toBe(['203.0.113.7' => null]);
    $this->visitAs('203.0.113.7', '/about')->assertRedirect(BLOCKED_PAGE);
});

it('keeps the flag out of the saved entry and the export', function () {
    $this->signIn();
    $this->blockAsEarlierVersion('10.0.0.*', 'old');

    $this->indexProps();
    $export = $this->get(cp_route('alt-inbound.export'))->streamedContent();

    expect(File::get($this->sitePath().'/'.Data::blockPath('10.0.0.*')))->not->toContain('problem')
        ->and($export)->not->toContain('problem')
        ->and($export)->toContain('old,10.0.0.*');
});
