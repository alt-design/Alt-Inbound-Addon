<?php

namespace AltDesign\AltInbound\Tests;

use AltDesign\AltInbound\Helpers\Data;
use AltDesign\AltInbound\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\Facades\YAML;
use Statamic\Statamic;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory($this->sitePath());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sitePath());

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // The addon checks the Statamic version as it boots, which happens before
        // AddonTestCase gets a chance to mock it out.
        \Facades\Statamic\Version::shouldReceive('get')->andReturn(
            ltrim(\Composer\InstalledVersions::getPrettyVersion('statamic/cms'), 'v')
        );

        $app->bind('filesystems.paths.standard', fn () => $this->sitePath());
        $app->usePublicPath($this->sitePath().'/public');

        $app['config']->set('statamic.routes.enabled', false);
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.stache.stores.users.directory', $this->sitePath().'/users');
        $app['config']->set('statamic.users.repositories.file.paths.roles', $this->sitePath().'/users/roles.yaml');
        $app['config']->set('statamic.users.repositories.file.paths.groups', $this->sitePath().'/users/groups.yaml');
        $app['config']->set('view.paths', [__DIR__.'/views']);
    }

    /**
     * Mount the addon's web routes and stand in for Statamic's frontend catch-all, in the order Statamic registers them.
     *
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router): void
    {
        Statamic::additionalWebRoutes();

        $router->middleware('web')
            ->get('{segments?}', fn () => 'page')
            ->where('segments', '^(?!'.config('statamic.cp.route').'(/|$)).*');
    }

    protected function sitePath(): string
    {
        return __DIR__.'/__fixtures__/site';
    }

    protected function block(string $ip, string $id = 'test'): void
    {
        $this->writeContent('blocks/'.base64_encode($ip).'.yaml', ['ip' => $ip, 'id' => $id]);
    }

    protected function useAllowList(): void
    {
        $this->writeContent('whitelist.yaml', []);
    }

    protected function useCustomView(string $name): void
    {
        $this->writeContent('custom-view.yaml', ['view' => $name]);
    }

    protected function visitAs(string $ip, string $uri): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->get($uri);
    }

    /**
     * Sign in to the control panel, as a super user unless permissions are given.
     *
     * @param  list<string>|null  $permissions
     */
    protected function signIn(?array $permissions = null): static
    {
        $user = User::make()->email('editor@example.com');

        if ($permissions === null) {
            $user->makeSuper();
        } else {
            Role::make('editor')->permissions(['access cp', ...$permissions])->save();
            $user->assignRole('editor');
        }

        $user->save();

        return $this->actingAs($user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function entries(): array
    {
        return (new Data('Inbound'))->all();
    }

    /**
     * @return list<string>
     */
    protected function listedIps(): array
    {
        return collect($this->entries())->pluck('ip')->sort()->values()->all();
    }

    /**
     * Put the addon's built front-end manifest where the default blocked page looks for it.
     */
    protected function publishAssets(): void
    {
        $manifest = $this->sitePath().'/public/vendor/alt-inbound/build/manifest.json';

        File::ensureDirectoryExists(dirname($manifest));
        File::copy(__DIR__.'/../resources/dist/build/manifest.json', $manifest);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeContent(string $path, array $data): void
    {
        $file = $this->sitePath().'/content/alt-inbound/'.$path;

        File::ensureDirectoryExists(dirname($file));
        File::put($file, YAML::dump($data));
    }
}
