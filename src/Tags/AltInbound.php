<?php namespace AltDesign\AltInbound\Tags;

use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Vite;

use Statamic\Tags\Tags;
use Statamic\Filesystem\Manager;

use AltDesign\AltCookiesAddon\Helpers\Data;

class AltInbound extends Tags
{
    protected static $handle = 'AltInbound';
    /**
     * The {{ AltInbound:FrontendAssets }} tag.
     * Puts the Vite assets on the frontend
     * @return string|array
     */
    public function FrontendAssets()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-inbound-addon/build');
        $assets = sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/js/alt-inbound-frontend.js'));
        $assets .= sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/css/alt-inbound-frontend.css'));
        return $assets;
    }

    /**
     * The {{ AltInbound:FrontendAssets }} tag.
     * Puts the Vite assets on the control panel
     * @return string|array
     */
    public function CPAssets()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-inbound-addon/build');
        $assets = sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/js/alt-inbound-addon.js'));
        $assets .= sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/css/alt-inbound-addon.css'));
        return $assets;
    }

    public function Background()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-inbound-addon/build');
        return $vite->asset('resources/img/alt-gradient-full.png');
    }

    public function Mobile()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-inbound-addon/build');
        return $vite->asset('resources/img/alt-gradient-mobile.png');
    }

    public function Blocked()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-inbound-addon/build');
        return $vite->asset('resources/img/stop-blocked-icon.png');
    }
}
