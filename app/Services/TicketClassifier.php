<?php

declare(strict_types=1);

namespace App\Services;

class TicketClassifier
{
    public function classify(string $subject, string $body): array
    {
        if (env('OPENAI_CLASSIFY_ENABLED', false)) {
            // TODO: Integrate openai-php/laravel here
            // Return ['category' => ..., 'explanation' => ..., 'confidence' => ...]
        }
        // Dummy fallback
        $categories = ['billing', 'technical', 'general', 'account', 'other'];
        return [
            'category' => $categories[array_rand($categories)],
            'explanation' => 'Dummy explanation for category.',
            'confidence' => round(mt_rand(50, 100) / 100, 2),
        ];
    }
}
