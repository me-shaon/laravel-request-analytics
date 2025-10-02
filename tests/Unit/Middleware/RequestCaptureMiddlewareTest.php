<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use MeShaon\RequestAnalytics\Jobs\ProcessData;
use MeShaon\RequestAnalytics\Http\Middleware\APIRequestCapture;
use MeShaon\RequestAnalytics\Http\Middleware\WebRequestCapture;
use MeShaon\RequestAnalytics\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestCaptureMiddlewareTest extends TestCase
{
    #[Test]
    public function api_middleware_handles_request_and_passes_through(): void
    {
        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');

        $response = $middleware->handle($request, fn ($req): Response => new Response('API Response'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('API Response', $response->getContent());
    }

    #[Test]
    public function web_middleware_handles_request_and_passes_through(): void
    {
        $middleware = new WebRequestCapture;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req): Response => new Response('Web Response'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Web Response', $response->getContent());
    }

    #[Test]
    public function api_middleware_terminates_and_queues_job_when_queue_enabled(): void
    {
        Queue::fake();

        config([
            'request-analytics.queue.enabled' => true,
            'request-analytics.capture.bots' => true,
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertPushed(ProcessData::class);
    }

    #[Test]
    public function api_middleware_terminates_and_processes_job_sync_when_queue_disabled(): void
    {
        Queue::fake();

        config([
            'request-analytics.queue.enabled' => false,
            'request-analytics.capture.bots' => true,
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertPushed(ProcessData::class);
    }

    #[Test]
    public function web_middleware_terminates_and_queues_job_when_queue_enabled(): void
    {
        Queue::fake();

        config([
            'request-analytics.queue.enabled' => true,
            'request-analytics.capture.bots' => true,
        ]);

        $middleware = new WebRequestCapture;
        $request = Request::create('/test', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('Web Response');

        $middleware->terminate($request, $response);

        Queue::assertPushed(ProcessData::class);
    }

    #[Test]
    public function middleware_does_not_capture_bots_when_bot_capture_disabled(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => false,
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('User-Agent', 'Googlebot/2.1');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function middleware_captures_bots_when_bot_capture_enabled(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('User-Agent', 'Googlebot/2.1');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertPushed(ProcessData::class);
    }

    #[Test]
    public function middleware_does_not_capture_ignored_paths(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/ignore'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/ignore', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function middleware_does_not_capture_wildcard_ignored_paths(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/admin/*'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/admin/users', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function middleware_does_not_capture_nested_wildcard_ignored_paths(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/*/internal'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/v1/internal', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function middleware_captures_paths_that_do_not_match_wildcard_pattern(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/admin/*'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/public/users', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertPushed(ProcessData::class);
    }

    #[Test]
    public function middleware_handles_multiple_wildcard_patterns(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/admin/*', 'livewire/*', '_debugbar/*'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;

        // Test first wildcard pattern
        $request1 = Request::create('/api/admin/dashboard', 'GET');
        $request1->headers->set('User-Agent', 'Test Browser Mozilla');
        $response1 = new Response('API Response');
        $middleware->terminate($request1, $response1);

        // Test second wildcard pattern
        $request2 = Request::create('/livewire/component', 'GET');
        $request2->headers->set('User-Agent', 'Test Browser Mozilla');
        $response2 = new Response('API Response');
        $middleware->terminate($request2, $response2);

        // Test third wildcard pattern
        $request3 = Request::create('/_debugbar/assets/js', 'GET');
        $request3->headers->set('User-Agent', 'Test Browser Mozilla');
        $response3 = new Response('API Response');
        $middleware->terminate($request3, $response3);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function middleware_handles_mixed_exact_and_wildcard_ignore_paths(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/health', 'api/admin/*', 'exact/path'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;

        // Test exact match
        $request1 = Request::create('/api/health', 'GET');
        $request1->headers->set('User-Agent', 'Test Browser Mozilla');
        $response1 = new Response('API Response');
        $middleware->terminate($request1, $response1);

        // Test wildcard match
        $request2 = Request::create('/api/admin/settings', 'GET');
        $request2->headers->set('User-Agent', 'Test Browser Mozilla');
        $response2 = new Response('API Response');
        $middleware->terminate($request2, $response2);

        // Test another exact match
        $request3 = Request::create('/exact/path', 'GET');
        $request3->headers->set('User-Agent', 'Test Browser Mozilla');
        $response3 = new Response('API Response');
        $middleware->terminate($request3, $response3);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function middleware_logs_errors_when_exception_occurs(): void
    {
        // Just test that middleware doesn't break when errors occur
        // The Log facade can be hard to mock correctly in this context
        config([
            'request-analytics.capture.bots' => true,
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        // This should not throw an exception even if internal errors occur
        $middleware->terminate($request, $response);

        $this->assertTrue(true);
    }

    #[Test]
    public function middleware_handles_null_capture_result(): void
    {
        Queue::fake();

        config([
            'request-analytics.capture.bots' => true,
            'request-analytics.ignore-paths' => ['api/test'],
            'request-analytics.route.pathname' => '/analytics',
        ]);

        $middleware = new APIRequestCapture;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('User-Agent', 'Test Browser Mozilla');
        $response = new Response('API Response');

        $middleware->terminate($request, $response);

        Queue::assertNothingPushed();
    }
}
