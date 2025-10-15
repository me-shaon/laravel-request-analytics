<?php

namespace MeShaon\RequestAnalytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MeShaon\RequestAnalytics\DTO\RequestDataDTO;
use MeShaon\RequestAnalytics\Services\RequestAnalyticsService;

class ProcessData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public RequestDataDTO $requestDataDTO)
    {
        $this->onQueue((string) config('request-analytics.queue.on_queue'));
    }

    public function handle(RequestAnalyticsService $requestAnalyticsService): void
    {
        $requestAnalyticsService->store($this->requestDataDTO);
    }
}
