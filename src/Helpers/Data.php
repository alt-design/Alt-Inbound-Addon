<?php
namespace AltDesign\AltInbound\Helpers;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Filesystem\Manager;

class Data
{
    public $type;
    public $manager;
    public $currentFile;
    public $data = [];
    public function __construct($type)
    {
        $this->type = $type;

        // New up Stat File Manager
        $this->manager = new Manager();

        // Check redirect folder exists
        if (!$this->manager->disk()->exists('content/alt-blocker')) {
            $this->manager->disk()->makeDirectory('content/alt-blocker');
        }
        if (!$this->manager->disk()->exists('content/alt-blocker/blocks')) {
            $this->manager->disk()->makeDirectory('content/alt-blocker/blocks');
        }
        $allBlocks = File::allFiles(base_path('/content/alt-blocker/blocks'));
        $allBlocks = collect($allBlocks)->sortByDesc(function ($file) {
            return $file->getCTime();
        });
        foreach ($allBlocks as $block) {
            $data = Yaml::parse(File::get($block));
            $this->data[] = $data;
        }
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

    public function setAll($data)
    {
        $this->data = $data;
        $this->manager->disk()->put('content/alt-blocker/blocks/' . base64_encode($data['ip']) . '.yaml', Yaml::dump($this->data));
    }

    public function saveAll($data)
    {
        foreach ($data as $redirect) {
            $this->setAll($redirect);
        }
    }

    public function setBlacklist($state)
    {
        if (!$state) {
            $this->manager->disk()->delete('content/alt-blocker/blacklist.yaml');
            return;
        }

        $this->manager->disk()->put('content/alt-blocker/blacklist.yaml', Yaml::dump([]));
        return;
    }

    static public function blacklisting()
    {
        return (int)((new Manager())->disk()->exists('content/alt-blocker/blacklist.yaml'));
    }

    public function setCustomView($name)
    {
        if (!$name) {
            $this->manager->disk()->delete('content/alt-blocker/custom-view.yaml');
            return;
        }
        $this->manager->disk()->put('content/alt-blocker/custom-view.yaml', Yaml::dump(['view' => $name]));
        return;
    }

    static public function customView()
    {
        $manager = (new Manager());
        if (!(new Manager())->disk()->exists('content/alt-blocker/custom-view.yaml')) {
            return null;
        }
        if (!($file = File::get(base_path('/content/alt-blocker/custom-view.yaml')))) {
            return null;
        }
        if (!($yaml = Yaml::parse($file))) {
            return null;
        }
        return $yaml['view'] ?? null;
    }

}
