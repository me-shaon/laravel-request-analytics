<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageViewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_range' => 'integer|min:1|max:365',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'request_category' => 'sometimes|string|in:web,api',
            'path' => 'string',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ];
    }
}
