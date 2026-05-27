<?php

namespace GrowthAtlas\Connector\Tests;

use GrowthAtlas\Connector\ConnectorServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $apiKey = 'test-key-for-phpunit-growthatlas';

    protected function getPackageProviders($app): array
    {
        return [ConnectorServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('growthatlas-connector.api_key', $this->apiKey);
        $app['config']->set('growthatlas-connector.signing_secret', null);
        $app['config']->set('growthatlas-connector.route_prefix', 'api/growthatlas/v1');
        $app['config']->set('growthatlas-connector.route_middleware', ['api']);
        $app['config']->set('growthatlas-connector.publishing', [
            'model'                 => \GrowthAtlas\Connector\Tests\Stubs\Post::class,
            'fields'                => ['title' => 'title', 'slug' => 'slug', 'body' => 'body'],
            'status_column'         => 'status',
            'growthatlas_id_column' => 'growthatlas_draft_id',
            'status_map'            => ['draft' => 'draft', 'published' => 'published'],
        ]);
        $app['config']->set('growthatlas-connector.pages', [
            'source'     => 'eloquent',
            'model'      => \GrowthAtlas\Connector\Tests\Stubs\Post::class,
            'url_column' => 'slug',
        ]);
        $app['config']->set('growthatlas-connector.entities', []);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        if (Schema::hasTable('posts')) {
            return;
        }
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('growthatlas_draft_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    protected function headers(bool $authenticated = true): array
    {
        $h = ['Accept' => 'application/json'];
        if ($authenticated) {
            $h['Authorization'] = "Bearer {$this->apiKey}";
        }
        return $h;
    }
}
