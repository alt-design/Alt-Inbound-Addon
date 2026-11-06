<?php namespace AltDesign\AltInbound;

use AltDesign\AltInbound\Support\AltToolsNav;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
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
            AltToolsNav::add($nav, 'privacy', 'Alt Inbound', 'alt-inbound.index', 'view alt-inbound', ['construction-barrier', 'shield-key']);
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
    }
}

