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


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('request-analytics.database.table', 'request_analytics');

        if ($connection = config('request-analytics.database.connection')) {
            $this->connection = $connection;
        }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        if (! config('request-analytics.pruning.enabled', false)) {
            return $this->whereRaw('1 = 0');
        }

        $days = config('request-analytics.pruning.days', 90);

        return static::where('visited_at', '<=', Carbon::now()->subDays($days));
    }
}
