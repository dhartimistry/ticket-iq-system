<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'rate_limit' => env('OPENAI_RATE_LIMIT', 60), // Requests per minute
    'classify_enabled' => env('OPENAI_CLASSIFY_ENABLED', true),
];
