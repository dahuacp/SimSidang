<?php

declare(strict_types=1);

return [
    'llm' => [
        'url' => env('ASSISTANT_LLM_URL', 'https://api.openai.com/v1'),
        'api_key' => env('ASSISTANT_LLM_API_KEY', ''),
        'model' => env('ASSISTANT_LLM_MODEL', 'gpt-4'),
        'system_prompt' => env('ASSISTANT_SYSTEM_PROMPT', 'You are a read-only virtual assistant for the SIMSIDANG academic session management system. You can only read aggregate data through the tools provided. You must never modify, insert, or delete any data. Answer in formal Indonesian.'),
    ],
    'rate_limit' => [
        'per_minute' => env('ASSISTANT_RATE_PER_MINUTE', 10),
        'per_conversation' => env('ASSISTANT_RATE_PER_CONVERSATION', 50),
    ],
];
