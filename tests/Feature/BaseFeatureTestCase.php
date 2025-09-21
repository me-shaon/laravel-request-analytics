<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use MeShaon\RequestAnalytics\Contracts\CanAccessAnalyticsDashboard;
use MeShaon\RequestAnalytics\Tests\TestCase;
use Mockery;

class BaseFeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock that implements both interfaces
        $user = Mockery::mock(Authenticatable::class, CanAccessAnalyticsDashboard::class);

        // Mock the canAccessAnalyticsDashboard method to return true
        $user->shouldReceive('canAccessAnalyticsDashboard')->andReturn(true);

        // Mock all required Authenticatable methods
        $user->shouldReceive('getAuthIdentifierName')->andReturn('id');
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        // Mock property access
        $user->id = 1;

        // Authenticate the mocked user
        $this->actingAs($user);

        // $this->withoutExceptionHandling();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
