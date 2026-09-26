<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ServiceProviderTest extends TestCase
{
    public function test_views_are_not_registered_when_filament_is_not_installed(): void
    {
        // Filament is not in require-dev, so it's not installed in the test environment.
        // When Filament is not available, the view namespace should not be registered.
        
        $this->assertFalse(
            class_exists(\Filament\Facades\Filament::class),
            'Filament should not be installed in the test environment'
        );

        // The 'growthatlas-connector' view namespace should not be registered
        $viewFinder = $this->app->make('view')->getFinder();
        $hints = $viewFinder->getHints();
        
        $this->assertArrayNotHasKey(
            'growthatlas-connector',
            $hints,
            'View namespace should not be registered when Filament is not installed'
        );
    }

    public function test_view_cache_does_not_fail_when_filament_is_not_installed(): void
    {
        // This is the core issue: view:cache should not fail when Filament is not installed
        
        $this->assertFalse(
            class_exists(\Filament\Facades\Filament::class),
            'Filament should not be installed in the test environment'
        );

        // Run view:cache - it should complete successfully
        $exitCode = Artisan::call('view:cache');
        
        $this->assertEquals(
            0,
            $exitCode,
            'view:cache should complete successfully when Filament is not installed'
        );
    }

    public function test_service_provider_boots_successfully_without_filament(): void
    {
        // The service provider should boot without errors even when Filament is not installed
        
        $this->assertFalse(
            class_exists(\Filament\Facades\Filament::class),
            'Filament should not be installed in the test environment'
        );

        // Make a simple request to ensure the service provider has booted
        $this->getJson('/api/growthatlas/v1/health', $this->headers())
             ->assertStatus(200)
             ->assertJson(['success' => true]);
    }
}
