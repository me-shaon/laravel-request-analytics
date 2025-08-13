<?php

namespace MeShaon\RequestAnalytics\Services;

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
        if (empty($userAgent)) {
            return true; // No user agent is suspicious
        }

        // Check user agent patterns
        $userAgentLower = strtolower($userAgent);
        foreach ($this->botPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        // Check IP ranges if provided
        if ($ipAddress && $this->isIpInBotRange($ipAddress)) {
            return true;
        }

        // Check for missing browser indicators
        if (! str_contains($userAgentLower, 'mozilla') &&
            ! str_contains($userAgentLower, 'opera') &&
            ! str_contains($userAgentLower, 'webkit')) {
            return true;
        }

        return false;
    }

    protected function isIpInBotRange(string $ip): bool
    {
        foreach ($this->botIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) == $subnet;
    }

    public function getBotName(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
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

        foreach ($botNames as $pattern => $name) {
            if (str_contains($userAgentLower, $pattern)) {
                return $name;
            }
        }

        return null;
    }
}
