<?php

use MeShaon\RequestAnalytics\Services\BotDetectionService;

it('detects common bots from user agent', function () {
    $service = new BotDetectionService;

    $botUserAgents = [
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
        'curl/7.68.0',
        'python-requests/2.25.1',
    ];

    foreach ($botUserAgents as $ua) {
        expect($service->isBot($ua))->toBeTrue();
    }
});

it('does not detect regular browsers as bots', function () {
    $service = new BotDetectionService;

    $browserUserAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0',
    ];

    foreach ($browserUserAgents as $ua) {
        expect($service->isBot($ua))->toBeFalse();
    }
});

it('identifies bot names correctly', function () {
    $service = new BotDetectionService;

    $tests = [
        'Googlebot/2.1' => 'Google',
        'bingbot/2.0' => 'Bing',
        'facebookexternalhit/1.1' => 'Facebook',
        'AhrefsBot/7.0' => 'Ahrefs',
    ];

    foreach ($tests as $ua => $expectedName) {
        expect($service->getBotName($ua))->toBe($expectedName);
    }
});

it('returns null for non-bot user agents', function () {
    $service = new BotDetectionService;

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124';
    expect($service->getBotName($ua))->toBeNull();
});
