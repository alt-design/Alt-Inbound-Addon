<?php namespace AltDesign\AltInbound\Helpers;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;
use Statamic\Yaml\ParseException;

class Data
{
    public const BLOCKS_DIRECTORY = 'content/alt-inbound/blocks';

    public const PROBLEM_INVALID = 'invalid';

    public const PROBLEM_NON_STANDARD = 'non-standard';

    public $type;
    public $manager;
    public $currentFile;
    public $data = [];

    /**
     * Path of each entry's file, relative to the site, keyed the same as $data.
     *
     * @var array<int, string>
     */
    private array $files = [];

    public function __construct($type)
    {
        $this->type = $type;

        // New up Stat File Manager
        $this->manager = new Manager();

        // Check redirect folder exists
        if (!$this->manager->disk()->exists('content/alt-inbound')) {
            $this->manager->disk()->makeDirectory('content/alt-inbound');
        }
        if (!$this->manager->disk()->exists(self::BLOCKS_DIRECTORY)) {
            $this->manager->disk()->makeDirectory(self::BLOCKS_DIRECTORY);
        }

        $this->manager->disk()->getFilesRecursively(self::BLOCKS_DIRECTORY)
            ->sortByDesc(fn ($path) => $this->manager->disk()->lastModified($path))
            ->each(fn ($path) => $this->load($path));
    }

    public function get($key)
    {
        if (!isset($this->data[$key])) {
            return null;
        }
        return $this->data[$key];
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;

        Yaml::dump($this->data, $this->currentFile);
    }

    public function all()
    {
        return $this->data;
    }

    /**
     * Every entry with a `problem` key saying why it may not match a visitor, or null when it is in standard form.
     *
     * Only entries saved before addresses were validated can have one: `invalid` for text that is not a single IP
     * address, which matches no visitor, and `non-standard` for an address not saved in its standard form, which
     * matches only a visitor whose address arrives written exactly that way. Entries with a problem come first, so they
     * can be found on a long list.
     *
     * @return list<array<string, mixed>>
     */
    public function allWithProblems(): array
    {
        $entries = array_map(
            fn (array $entry, string $path) => [...$entry, 'problem' => self::problem($entry['ip'], $path)],
            $this->data,
            $this->files,
        );

        usort($entries, fn (array $a, array $b) => ($b['problem'] !== null) <=> ($a['problem'] !== null));

        return $entries;
    }

    /**
     * Add an IP address to the list in its standard form, leaving it alone if it is already there.
     *
     * @return array{ip: string, id: string}|null The new entry, or null when the address was already listed.
     *
     * @throws InvalidArgumentException When the value is not a single IP address.
     */
    public function add(string $ip): ?array
    {
        $ip = Ip::canonical($ip) ?? throw new InvalidArgumentException('Not a single IP address.');
        $path = self::blockPath($ip);

        if ($this->manager->disk()->exists($path)) {
            return null;
        }

        $entry = ['ip' => $ip, 'id' => (string) Str::uuid()];
        $this->manager->disk()->put($path, Yaml::dump($entry));

        $this->files[] = $path;
        $this->data[] = $entry;

        return $entry;
    }

    /**
     * Remove the entry with this id and IP text, or failing that the first entry with this IP text.
     *
     * Entries are found among the files on disk, so one saved in any form can be removed. The text is compared without
     * surrounding whitespace, because requests arrive trimmed.
     *
     * @return array<string, mixed>|null The removed entry, or null when nothing matched.
     */
    public function remove(?string $id, ?string $ip): ?array
    {
        $ip = trim((string) $ip);

        $index = $this->find(fn ($entry) => $entry['id'] === $id && trim($entry['ip']) === $ip)
            ?? $this->find(fn ($entry) => trim($entry['ip']) === $ip);

        if ($index === null) {
            return null;
        }

        $entry = $this->data[$index];
        $this->manager->disk()->delete($this->files[$index]);

        unset($this->data[$index], $this->files[$index]);
        $this->data = array_values($this->data);
        $this->files = array_values($this->files);

        return $entry;
    }

    /**
     * Whether an IP address is on the list, whichever way it is written.
     *
     * The address as given is checked as well as its standard form, so an entry saved by an earlier version in exactly
     * that form keeps matching.
     */
    public static function isListed(?string $ip): bool
    {
        $canonical = Ip::canonical($ip);

        if ($canonical === null) {
            return false;
        }

        $disk = (new Manager())->disk();

        return $disk->exists(self::blockPath($canonical)) || ($ip !== $canonical && $disk->exists(self::blockPath($ip)));
    }

    public static function blockPath(string $ip): string
    {
        return self::BLOCKS_DIRECTORY.'/'.base64_encode($ip).'.yaml';
    }

    public function setBlacklist($state)
    {
        if (!$state) {
            $this->manager->disk()->put('content/alt-inbound/whitelist.yaml', Yaml::dump([]));

            return;
        }

        $this->manager->disk()->delete('content/alt-inbound/whitelist.yaml');
        return;
    }

    /**
     * Whether the site is in allow-list mode, from ALT_INBOUND_MODE when it is set and the control panel setting
     * otherwise.
     *
     * @return int 1 in allow-list mode, 0 in block-list mode.
     */
    static public function whiteListing()
    {
        if (($mode = Mode::configured()) !== null) {
            return (int) ($mode === Mode::ALLOW);
        }

        return (int)((new Manager())->disk()->exists('content/alt-inbound/whitelist.yaml'));
    }

    public function setCustomView($name)
    {
        if (!$name) {
            $this->manager->disk()->delete('content/alt-inbound/custom-view.yaml');
            return;
        }
        $this->manager->disk()->put('content/alt-inbound/custom-view.yaml', Yaml::dump(['view' => $name]));
        return;
    }

    static public function customView()
    {
        if (!($file = (new Manager())->disk()->get('content/alt-inbound/custom-view.yaml'))) {
            return null;
        }
        if (!($yaml = Yaml::parse($file))) {
            return null;
        }
        return $yaml['view'] ?? null;
    }

    /**
     * Read one entry's file, skipping it if it cannot be parsed or has no IP address.
     */
    private function load(string $path): void
    {
        try {
            $entry = Yaml::parse($this->manager->disk()->get($path));
        } catch (ParseException) {
            return;
        }

        if (! is_array($entry) || ! isset($entry['ip'])) {
            return;
        }

        $this->files[] = $path;
        $this->data[] = [...$entry, 'ip' => (string) $entry['ip'], 'id' => isset($entry['id']) ? (string) $entry['id'] : null];
    }

    /**
     * @return 'invalid'|'non-standard'|null
     */
    private static function problem(string $ip, string $path): ?string
    {
        $canonical = Ip::canonical($ip);

        if ($canonical === null) {
            return self::PROBLEM_INVALID;
        }

        return $path === self::blockPath($canonical) ? null : self::PROBLEM_NON_STANDARD;
    }

    /**
     * @param  callable(array<string, mixed>): bool  $matches
     */
    private function find(callable $matches): ?int
    {
        foreach ($this->data as $index => $entry) {
            if ($matches($entry)) {
                return $index;
            }
        }

        return null;
    }
}
