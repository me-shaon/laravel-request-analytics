<?php

namespace MeShaon\RequestAnalytics\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class VisitorTrackingService
{
    protected string $cookieName = 'ra_visitor_id';

    protected int $cookieLifetime = 525600; // 1 year in minutes

    public function getVisitorId(Request $request): string
    {
        // Check for existing visitor ID in cookie
        if ($visitorId = $request->cookie($this->cookieName)) {
            return $visitorId;
        }

        // Generate new visitor ID
        $visitorId = $this->generateVisitorId($request);

        // Set cookie for future requests
        Cookie::queue($this->cookieName, $visitorId, $this->cookieLifetime);

        return $visitorId;
    }

    public function generateVisitorId(Request $request): string
    {
        // Create a fingerprint based on various factors
        $fingerprint = $this->createFingerprint($request);

        // Generate a unique ID combining fingerprint and random string
        return hash('sha256', $fingerprint.Str::random(32));
    }

    protected function createFingerprint(Request $request): string
    {
        $components = [
            $request->header('User-Agent', ''),
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),
            $request->ip(),
            // Screen resolution and color depth would need to be collected via JavaScript
        ];

        return implode('|', array_filter($components));
    }

    public function getSessionId(Request $request): string
    {
        // Use Laravel's session ID if available
        if ($request->hasSession()) {
            return $request->session()->getId();
        }

        // Fallback to generating a session-like ID
        return $this->generateSessionId($request);
    }

    protected function generateSessionId(Request $request): string
    {
        // Create a session ID based on visitor ID and timestamp
        $visitorId = $this->getVisitorId($request);
        $timestamp = floor(time() / 1800); // 30-minute sessions

        return hash('sha256', $visitorId.'|'.$timestamp);
    }

    public function isNewVisitor(Request $request): bool
    {
        return ! $request->hasCookie($this->cookieName);
    }

    public function isReturningVisitor(Request $request): bool
    {
        return $request->hasCookie($this->cookieName);
    }
}
