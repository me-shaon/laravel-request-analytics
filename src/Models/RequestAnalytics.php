<?php

namespace MeShaon\RequestAnalytics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RequestAnalytics extends Model
{
    use HasFactory, MassPrunable;

    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        $days = config('request-analytics.pruning.days', 90);

        return static::where('visited_at', '<', Carbon::now()->subDays($days));
    }
}
