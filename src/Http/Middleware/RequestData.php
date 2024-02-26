<?php

namespace MeShaon\RequestAnalytics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MeShaon\RequestAnalytics\Http\Jobs\ProcessData;
use Symfony\Component\HttpFoundation\Response;

class RequestData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $content = $response->getContent();
        $pageTitle = $this->extractPageTitle($content);
        $browserInfo = $this->parseUserAgent($request->header('User-Agent'));
        $requestData = [
            'url' => $request->url(),
            'page_title' => $pageTitle,
            'ip_address' => $request->ip() ?? $request->server('REMOTE_ADDR'),
            'operating_system' => $browserInfo['operating_system'],
            'browser' => $browserInfo['browser'],
            'device' => $browserInfo['device'],
            'screen' => '',
            'referrer' => $request->header('referer', ''),
            'country' => $request->header('CF-IPCountry', ''),
            'city' => '',
            'language' => $request->header('Accept-Language', ''),
            'query_params' => json_encode($request->getQueryString()),
            'session_id' => session()->getId(),
            'user_id' => Auth::id(),
            'http_method' => $request->method(),
            'request_type' => '',
            'response_time' => '',
        ];
        ProcessData::dispatch($requestData);
    }

    private function extractPageTitle($content)
    {
        $matches = [];
        preg_match('/<title>(.*?)<\/title>/i', $content, $matches);

        return isset($matches[1]) ? $matches[1] : '';
    }

    private function parseUserAgent($userAgent)
    {
        $operating_system = $this->getOperatingSystem($userAgent);
        $browser = $this->getBrowser($userAgent);
        $device = $this->getDevice($userAgent);

        return compact('operating_system', 'browser', 'device');
    }

    private function getOperatingSystem($userAgent)
    {
        $operatingSystem = 'Unknown';
        $osRegexes = [
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6\.3/i' => 'Windows 8.1',
            '/windows nt 6\.2/i' => 'Windows 8',
            '/windows nt 6\.1/i' => 'Windows 7',
            '/windows nt 6\.0/i' => 'Windows Vista',
            '/windows nt 5\.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5\.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5\.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iOS',
            '/ipod/i' => 'iOS',
            '/ipad/i' => 'iOS',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile',
        ];
        foreach ($osRegexes as $regex => $os) {
            if (preg_match($regex, $userAgent)) {
                $operatingSystem = $os;
                break;
            }
        }

        return $operatingSystem;
    }

    private function getBrowser($userAgent)
    {
        $browser = 'Unknown';
        $browserRegexes = [
            '/msie|trident/i' => 'Internet Explorer',
            '/edge/i' => 'Edge',
            '/edg/i' => 'Edge',
            '/firefox/i' => 'Firefox',
            '/brave/i' => 'Brave',
            '/chrome/i' => 'Chrome',
            '/safari/i' => 'Safari',
            '/opera|opr/i' => 'Opera',
        ];
        foreach ($browserRegexes as $regex => $br) {
            if (preg_match($regex, $userAgent)) {
                $browser = $br;
                break;
            }
        }

        return $browser;
    }

    private function getDevice($userAgent)
    {
        $device = 'Unknown';
        $deviceRegexes = [
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/windows phone/i' => 'Windows Phone',
            '/mobile/i' => 'Mobile',
            '/tablet/i' => 'Tablet',
        ];
        foreach ($deviceRegexes as $regex => $dev) {
            if (preg_match($regex, $userAgent)) {
                $device = $dev;
                break;
            }
        }

        return $device;
    }
}
