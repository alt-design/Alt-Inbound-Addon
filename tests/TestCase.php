<?php

namespace AltDesign\AltInbound\Tests;

use AltDesign\AltInbound\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Statamic\Facades\YAML;
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

        $app['config']->set('statamic.routes.enabled', false);
    }

    /**
     * Stand in for Statamic's frontend catch-all, so every URL reaches the web middleware group.
     *
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')
            ->get('{segments?}', fn () => 'page')
            ->where('segments', '.*');
    }

    protected function sitePath(): string
    {
        return __DIR__.'/__fixtures__/site';
    }

    protected function block(string $ip): void
    {
        $this->writeContent('blocks/'.base64_encode($ip).'.yaml', ['ip' => $ip, 'id' => 'test']);
    }

    protected function useAllowList(): void
    {
        $this->writeContent('whitelist.yaml', []);
    }

    protected function visitAs(string $ip, string $uri): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->get($uri);
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
