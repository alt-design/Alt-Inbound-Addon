<?php namespace AltDesign\AltInbound\Tags;

use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Vite;

use Statamic\Tags\Tags;
use Statamic\Filesystem\Manager;

use AltDesign\AltCookiesAddon\Helpers\Data;

class AltInbound extends Tags
{
    protected $viteBuild = 'vendor/alt-inbound/build';
    protected $viteHot = __DIR__ . '/../../resources/dist/hot';
    protected $assetPathProd = '/vendor/alt-inbound/img/';
    protected static $handle = 'AltInbound';

    /**
     * The {{ AltInbound:FrontendAssets }} tag.
     * Puts the Vite assets on the frontend
     * @return string|array
     */
    public function FrontendAssets()
    {
        $vite = (new Vite)->useHotfile( $this->viteHot)->useBuildDirectory($this->viteBuild);
        $assets = sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/js/alt-inbound-frontend.js'));
        $assets .= sprintf('<link rel="stylesheet" href="%s">', $vite->asset('resources/css/alt-inbound-frontend.css'));
        return $assets;
    }

    /**
     * The {{ AltInbound:FrontendAssets }} tag.
     * Puts the Vite assets on the control panel
     * @return string|array
     */
    public function CPAssets()
    {
        $vite = (new Vite)->useHotfile( $this->viteHot)->useBuildDirectory($this->viteBuild);
        $assets = sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/js/alt-inbound-addon.js'));
        $assets .= sprintf('<link rel="stylesheet" href="%s">', $vite->asset('resources/css/alt-inbound-addon.css'));
        return $assets;
    }

    public function Background()
    {
        return $this->assetPathProd . 'alt-gradient-full.png';
    }

    public function Mobile()
    {
        return $this->assetPathProd . 'alt-gradient-mobile.png';
    }

    public function Blocked()
    {
        return $this->assetPathProd . 'alt-blocked-icon.png';
    }
}
