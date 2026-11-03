<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function importCsv(string $contents, string $name = 'blocks.csv')
{
    return test()->postJson(cp_route('alt-inbound.import'), [
        'file' => UploadedFile::fake()->createWithContent($name, $contents),
    ]);
}

describe('import', function () {
    it('adds each IP address in its standard form, once', function () {
        $this->signIn();

        importCsv("id,ip\n1,203.0.113.7\n2,2001:DB8::1\n3,2001:db8:0:0:0:0:0:1\n4, 198.51.100.20 \n")->assertRedirect();

        expect($this->listedIps())->toBe(['198.51.100.20', '2001:db8::1', '203.0.113.7']);
    });

    it('ignores the id column and makes its own ids', function () {
        $this->signIn();

        importCsv("id,ip\nchosen-by-the-file,203.0.113.7\n");

        expect(Str::isUuid($this->entries()[0]['id']))->toBeTrue();
    });

    it('leaves entries already on the list as they are', function () {
        $this->signIn();
        $this->block('203.0.113.7', 'existing');
        $this->block('198.51.100.20', 'other');

        importCsv("id,ip\nother,203.0.113.7\n");

        expect(collect($this->entries())->pluck('id', 'ip')->all())
            ->toEqual(['203.0.113.7' => 'existing', '198.51.100.20' => 'other']);
    });

    it('finds the ip column by its header', function (string $contents) {
        $this->signIn();

        importCsv($contents)->assertRedirect();

        expect($this->listedIps())->toBe(['203.0.113.7']);
    })->with([
        'ip only' => "ip\n203.0.113.7\n",
        'columns the other way round' => "ip,id\n203.0.113.7,1\n",
        'an extra column' => "id,ip,note\n1,203.0.113.7,office\n",
        'a capitalised header' => "ID,IP\n1,203.0.113.7\n",
        'a byte order mark' => "\xEF\xBB\xBFip\n203.0.113.7\n",
        'Windows line endings' => "id,ip\r\n1,203.0.113.7\r\n",
        'blank lines' => "id,ip\n\n1,203.0.113.7\n\n",
    ]);

    it('accepts a file with a header and no rows', function () {
        $this->signIn();

        importCsv("id,ip\n")->assertRedirect();

        expect($this->entries())->toBeEmpty();
    });

    it('imports nothing when any row does not hold a single IP address', function (string $contents, string $message) {
        $this->signIn();
        $this->block('192.0.2.1');

        importCsv($contents)->assertUnprocessable()->assertJsonValidationErrors(['file' => $message]);

        expect($this->listedIps())->toBe(['192.0.2.1']);
    })->with([
        'a range' => ["id,ip\n1,203.0.113.7\n2,10.0.0.0/8\n", 'Nothing was imported. Check row 3: it needs a single IP address.'],
        'a short row' => ["id,ip\n1,203.0.113.7\n2\n", 'Nothing was imported. Check row 3: it needs a single IP address.'],
        'several bad rows' => ["id,ip\n1,a\n2,203.0.113.7\n3,b\n", 'Nothing was imported. Check rows 2, 4: each needs a single IP address.'],
        'no header' => ["203.0.113.7\n198.51.100.20\n", 'The file needs a header row with an ip column, as in the export.'],
        'an empty first line' => ["\nip\n203.0.113.7\n", 'The file needs a header row with an ip column, as in the export.'],
    ]);

    it('names the first twenty bad rows and counts the rest', function () {
        $this->signIn();

        $response = importCsv("ip\n".str_repeat("bad\n", 25));

        expect($response->json('errors.file.0'))->toBe(
            'Nothing was imported. Check rows '.implode(', ', range(2, 21)).' and 5 more: each needs a single IP address.'
        );
    });

    it('rejects a request without a CSV file', function (array $payload) {
        $this->signIn();

        $this->postJson(cp_route('alt-inbound.import'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    })->with([
        'no file' => fn () => [[]],
        'an empty file' => fn () => [['file' => UploadedFile::fake()->createWithContent('blocks.csv', '')]],
        'an image' => fn () => [['file' => UploadedFile::fake()->image('blocks.png')]],
        'a file over 1MB' => fn () => [['file' => UploadedFile::fake()->create('blocks.csv', 1025, 'text/csv')]],
    ]);

    it('logs who imported and how many entries were added, without the IP addresses', function () {
        Log::spy();
        $this->signIn();
        $this->block('203.0.113.7');

        importCsv("ip\n203.0.113.7\n198.51.100.20\n");

        Log::shouldHaveReceived('info')->once()->with('Alt Inbound: blocks imported', [
            'user' => auth()->id(),
            'added' => 1,
            'already listed' => 1,
        ]);
    });
});

describe('export', function () {
    it('writes the id and IP address of every entry', function () {
        $this->signIn();
        $this->block('203.0.113.7', 'first');

        $csv = $this->get(cp_route('alt-inbound.export'))->assertOk()->streamedContent();

        expect($csv)->toBe("id,ip\nfirst,203.0.113.7\n");
    });

    it('stops a spreadsheet running a value as a formula', function (string $value) {
        $this->signIn();
        $this->block($value, $value);

        $csv = $this->get(cp_route('alt-inbound.export'))->streamedContent();
        $rows = array_map(fn ($line) => str_getcsv($line, escape: ''), explode("\n", trim($csv)));

        expect($rows[1])->toBe(["'".$value, "'".$value]);
    })->with([
        'equals' => '=HYPERLINK("https://example.com")',
        'plus' => '+1+1',
        'minus' => '-1+1',
        'at' => '@SUM(1)',
        'tab' => "\t=1",
        'carriage return' => "\r=1",
    ]);

    it('gives back the same list when its export is imported', function () {
        $this->signIn();
        $this->block('203.0.113.7');
        $this->block('2001:db8::1');

        $csv = $this->get(cp_route('alt-inbound.export'))->streamedContent();
        $before = $this->listedIps();

        importCsv($csv)->assertRedirect();

        expect($this->listedIps())->toBe($before);
    });
});
