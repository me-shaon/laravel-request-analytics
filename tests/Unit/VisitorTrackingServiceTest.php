<?php

use Illuminate\Http\Request;
use MeShaon\RequestAnalytics\Services\VisitorTrackingService;

it('generates unique visitor id', function () {
    $service = new VisitorTrackingService;
    $request = Request::create('/test', 'GET');

    $visitorId1 = $service->generateVisitorId($request);
    $visitorId2 = $service->generateVisitorId($request);

    expect($visitorId1)->toBeString();
    expect($visitorId1)->toHaveLength(64); // SHA256 hash length
    expect($visitorId1)->not->toBe($visitorId2); // Should be different due to random component
});

it('creates consistent fingerprint for same request', function () {
    $service = new VisitorTrackingService;

    $request = Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');
    $request->headers->set('Accept-Language', 'en-US');

    // Use reflection to test protected method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('createFingerprint');
    $method->setAccessible(true);

    $fingerprint1 = $method->invoke($service, $request);
    $fingerprint2 = $method->invoke($service, $request);

    expect($fingerprint1)->toBe($fingerprint2);
});

it('detects new visitors', function () {
    $service = new VisitorTrackingService;
    $request = Request::create('/test', 'GET');

    expect($service->isNewVisitor($request))->toBeTrue();
    expect($service->isReturningVisitor($request))->toBeFalse();
});

it('generates session id', function () {
    $service = new VisitorTrackingService;
    $request = Request::create('/test', 'GET');

    $sessionId = $service->getSessionId($request);

    expect($sessionId)->toBeString();
    expect($sessionId)->toHaveLength(64);
});
