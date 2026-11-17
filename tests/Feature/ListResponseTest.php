<?php

it('sends the list and its warnings back after each change', function () {
    config(['alt-inbound.mode' => 'allow']);
    $this->signIn();

    $this->postJson(cp_route('alt-inbound.create'), ['ip' => '203.0.113.7'])
        ->assertOk()
        ->assertJsonPath('data.0.ip', '203.0.113.7')
        ->assertJsonPath('warnings', []);

    $this->postJson(cp_route('alt-inbound.delete'), ['ip' => '203.0.113.7'])
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('warnings', ['Allow-list mode is on and the list is empty, so every visitor to the site is sent to the blocked page.']);
});

it('sends the warnings back after the mode is switched', function () {
    $this->signIn();

    $this->postJson(cp_route('alt-inbound.blacklist'), ['blacklist' => false])
        ->assertOk()
        ->assertJsonCount(2, 'warnings');
});
