<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Services;

use MeShaon\RequestAnalytics\Exceptions\BotDetectionException;

class BotDetectionService
{
    protected array $botPatterns = [
        'bot', 'crawler', 'spider', 'scraper', 'facebookexternalhit',
        'facebookcatalog', 'twitterbot', 'linkedinbot', 'whatsapp',
        'slackbot', 'discord', 'telegram', 'skype', 'pinterest',
        'tumblr', 'reddit', 'quora', 'lighthouse', 'gtmetrix',
        'pingdom', 'uptimerobot', 'statuscake', 'newrelic',
        'appinsights', 'googlebot', 'bingbot', 'yandexbot',
        'duckduckbot', 'baiduspider', 'sogou', 'exabot', 'konqueror',
        'ia_archiver', 'ahrefsbot', 'semrushbot', 'dotbot', 'mj12bot',
        'blexbot', 'dataprovider', 'dataforseo', 'megaindex',
        'serpstatbot', 'petalbot', 'amazonbot', 'applebot',
        'chrome-lighthouse', 'headlesschrome', 'phantomjs', 'selenium',
        'puppeteer', 'playwright', 'webdriver', 'wget', 'curl',
        'python-requests', 'python-urllib', 'go-http-client',
        'java/', 'apache-httpclient', 'okhttp', 'postman',
        'insomnia', 'paw/', 'rest-client', 'ruby/', 'perl/',
        'php/', 'node-fetch', 'axios/', 'got/', 'superagent',
    ];

    protected array $botIpRanges = [
        // Google
        '66.249.64.0/19',
        '66.249.64.0/20',
        '66.249.80.0/20',
        // Facebook
        '31.13.24.0/21',
        '31.13.64.0/18',
        '66.220.144.0/20',
        '69.63.176.0/20',
        '69.171.224.0/19',
        '74.119.76.0/22',
        // Twitter
        '199.16.156.0/22',
        '199.59.148.0/22',
        // Microsoft/Bing
        '40.77.167.0/24',
        '157.55.39.0/24',
        '207.46.13.0/24',
    ];

    public function isBot(?string $userAgent, ?string $ipAddress = null): bool
    {
        if (in_array($userAgent, [null, '', '0'], true)) {
            return true; // No user agent is suspicious
        }

        // Check user agent patterns using collection methods
        $userAgentLower = strtolower($userAgent);
        $hasPattern = collect($this->botPatterns)
            ->contains(fn ($pattern): bool => str_contains($userAgentLower, (string) $pattern));

        if ($hasPattern) {
            return true;
        }

        // Check IP ranges if provided
        if ($ipAddress && $this->isIpInBotRange($ipAddress)) {
            return true;
        }

        // Check for missing browser indicators
        return ! str_contains($userAgentLower, 'mozilla') &&
            ! str_contains($userAgentLower, 'opera') &&
            ! str_contains($userAgentLower, 'webkit');
    }

    protected function isIpInBotRange(string $ip): bool
    {
        return collect($this->botIpRanges)
            ->contains(fn ($range): bool => $this->ipInRange($ip, $range));
    }

    protected function ipInRange(string $ip, string $range): bool
    {
        if (! str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);

        // Skip IPv6 addresses for now (ip2long only works for IPv4)
        if (str_contains($ip, ':') || str_contains($subnet, ':')) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            throw new BotDetectionException(
                null,
                $ip,
                "Invalid IP address format during bot detection: IP={$ip}, Range={$range}"
            );
        }

        $mask = -1 << (32 - $bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }

    public function getBotName(?string $userAgent): ?string
    {
        if (in_array($userAgent, [null, '', '0'], true)) {
            return null;
        }

        $userAgentLower = strtolower($userAgent);

        $botNames = [
            'googlebot' => 'Google',
            'bingbot' => 'Bing',
            'slurp' => 'Yahoo',
            'duckduckbot' => 'DuckDuckGo',
            'baiduspider' => 'Baidu',
            'yandexbot' => 'Yandex',
            'facebookexternalhit' => 'Facebook',
            'twitterbot' => 'Twitter',
            'linkedinbot' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'slackbot' => 'Slack',
            'discord' => 'Discord',
            'ahrefsbot' => 'Ahrefs',
            'semrushbot' => 'SEMrush',
            'lighthouse' => 'Google Lighthouse',
            'gtmetrix' => 'GTmetrix',
            'pingdom' => 'Pingdom',
            'uptimerobot' => 'UptimeRobot',
        ];

        return collect($botNames)
            ->first(fn ($name, $pattern): bool => str_contains($userAgentLower, $pattern));
    }
}
