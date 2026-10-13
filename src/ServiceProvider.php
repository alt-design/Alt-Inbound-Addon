<?php namespace AltDesign\AltInbound;

use Facades\Statamic\Version;
use Illuminate\Support\Str;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/alt-inbound-addon.js',
            'resources/css/alt-inbound-addon.css',
            'resources/js/alt-inbound-frontend.js',
            'resources/css/alt-inbound-frontend.css'
        ],
        'publicDirectory' => 'resources/dist',
    ];

    protected $publishables = [
        __DIR__.'/../resources/dist' => '',
        __DIR__.'/../resources/img' => 'img',
    ];

    protected $tags = [
        \AltDesign\AltInbound\Tags\AltInbound::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \AltDesign\AltInbound\Http\Middleware\CheckForBlocks::class,
        ],
    ];

    public function addToNav()
    {
        Nav::extend(function ($nav) {
            $nav->content('Alt Inbound')
                ->section('Tools')
                ->route('alt-inbound.index')
                ->can('view alt-inbound')
                ->icon(config('alt-inbound.alt_inbound_icon'));
        });
    }

    /**
     * Register the permission that controls who can see and manage the IP blocks.
     *
     * @return void
     */
    public function registerPermissions()
    {
        Permission::register('view alt-inbound')
            ->label('View and manage Alt Inbound IP blocks');
    }

    public function bootAddon()
    {
        $this->addToNav();
        $this->registerPermissions();

        // Statamic >= V6 - unbind the settings blueprint to remove the default settings page and permissions 
        // as we are handling this manually instead
        if(intval(Str::before(Version::get(), '.')) >= 6) {
            app()->offsetUnset("statamic.addons.alt-inbound.settings_blueprint");
        }
    }
}

