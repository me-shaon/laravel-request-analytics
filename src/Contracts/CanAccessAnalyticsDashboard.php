<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Contracts;

interface CanAccessAnalyticsDashboard
{
    public function canAccessAnalyticsDashboard(): bool;
}
