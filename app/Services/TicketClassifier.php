<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Client;
use RuntimeException;
use InvalidArgumentException;

/**
 * Handles ticket classification using AI and fallback methods.
 */
class TicketClassifier
{
    private const VALID_CATEGORIES = ['billing', 'technical', 'account', 'general', 'other'];
    private const MIN_CONFIDENCE = 0.0;
    private const MAX_CONFIDENCE = 1.0;
    private const DEFAULT_CONFIDENCE = 0.3;

    /**
     * Category definitions with associated keywords and descriptions.
     * Used for fallback classification when AI is unavailable.
     */
    private array $categoryKeywords = [
        'billing' => [
            'keywords' => [
                'payment', 'invoice', 'charge', 'refund', 'billing',
                'subscription', 'price', 'cost', 'fee', 'credit card',
                'money', 'overcharge'
            ],
            'description' => 'Payment, billing, and financial issues'
        ],
        'technical' => [
            'keywords' => [
                'not working', 'broken', 'error', 'bug', 'crash', 'slow',
                'login', 'password', 'upload', 'download', 'sync',
                'server', 'database', 'api', 'system'
            ],
            'description' => 'Technical problems and system issues'
        ],
        'account' => [
            'keywords' => [
                'account', 'profile', 'username', 'email', 'settings',
                'preferences', 'access', 'permissions', 'registration',
                'signup'
            ],
            'description' => 'User account and profile related issues'
        ],
        'general' => [
            'keywords' => [
                'how to', 'question', 'help', 'information', 'guide',
                'tutorial', 'feature', 'documentation', 'support'
            ],
            'description' => 'General inquiries and information requests'
        ],
        'other' => [
            'keywords' => [],
            'description' => 'Issues that don\'t fit other categories'
        ]
    ];

    public function classify(string $subject, string $body): array
    {
        if (env('OPENAI_CLASSIFY_ENABLED', false)) {
            return $this->classifyWithOpenAI($subject, $body);
        }
        
        // Requirements: "return random category & dummy explanation/confidence"
        return $this->classifyWithRandom($subject, $body);
    }

    private function classifyWithKeywords(string $subject, string $body): array
    {
        $text = strtolower($subject . ' ' . $body);
        $scores = [];
        
        foreach ($this->categoryKeywords as $category => $data) {
            $score = 0;
            $matchedKeywords = [];
            
            foreach ($data['keywords'] as $keyword) {
                if (strpos($text, strtolower($keyword)) !== false) {
                    $score++;
                    $matchedKeywords[] = $keyword;
                }
            }
            
            if ($score > 0) {
                $scores[$category] = [
                    'score' => $score,
                    'keywords' => $matchedKeywords
                ];
            }
        }
        
        if (empty($scores)) {
            return [
                'category' => 'other',
                'explanation' => 'No specific keywords matched. Categorized as general inquiry.',
                'confidence' => 0.3
            ];
        }
        
        // Find category with highest score
        $bestCategory = array_keys($scores, max($scores))[0];
        $bestScore = $scores[$bestCategory];
        
        $confidence = min(0.95, 0.4 + ($bestScore['score'] * 0.15));
        
        return [
            'category' => $bestCategory,
            'explanation' => sprintf(
                'Classified as %s based on keywords: %s. %s',
                $bestCategory,
                implode(', ', $bestScore['keywords']),
                $this->categoryKeywords[$bestCategory]['description']
            ),
            'confidence' => round($confidence, 2)
        ];
    }

    private function classifyWithOpenAI(string $subject, string $body): array
    {
        try {
            if (!config('openai.classify_enabled', false)) {
                throw new \Exception('OpenAI classification is disabled');
            }

            $apiKey = config('openai.api_key');
            
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key not configured');
            }
            
            $client = \OpenAI::client($apiKey);
            
            $systemPrompt = 'You are a help desk ticket classifier. Analyze the ticket and return ONLY a JSON response with exactly these keys:
                - "category": one of "billing", "technical", "account", "general", or "other"
                - "explanation": brief explanation (1-2 sentences) why you chose this category
                - "confidence": decimal between 0.0 and 1.0 representing your confidence

                Categories defined as:
                - billing: Payment, invoice, subscription, refund issues
                - technical: Software bugs, system errors, hardware problems, functionality issues
                - account: User profile, login, registration, access issues
                - general: Questions, how-to requests, information inquiries
                - other: Issues that don\'t fit the above categories

                Respond with JSON only, no other text.';

            $userPrompt = "Subject: {$subject}\n\nBody: {$body}";
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 200,
                'temperature' => 0.3, // Lower temperature for more consistent classification
            ]);
            
            $content = $response->choices[0]->message->content;
            
            // Parse JSON response
            $result = json_decode($content, true);
            
            if (!$result || !isset($result['category'], $result['explanation'], $result['confidence'])) {
                throw new \Exception('Invalid OpenAI response format');
            }
            
            // Validate category
            $validCategories = ['billing', 'technical', 'account', 'general', 'other'];
            if (!in_array($result['category'], $validCategories)) {
                $result['category'] = 'other';
            }
            
            // Validate confidence
            $result['confidence'] = max(0.0, min(1.0, (float) $result['confidence']));
            
            return [
                'category' => $result['category'],
                'explanation' => trim($result['explanation']),
                'confidence' => round($result['confidence'], 2)
            ];
            
        } catch (\Exception $e) {
            Log::warning('OpenAI classification failed', [
                'error' => $e->getMessage(),
                'subject' => $subject
            ]);
            
            // Fallback to random classification on error
            return $this->classifyWithRandom($subject, $body);
        }
    }

    private function classifyWithRandom(string $subject, string $body): array
    {
        // Requirements: "return random category & dummy explanation/confidence"
        $categories = ['billing', 'technical', 'general', 'account', 'other'];
        return [
            'category' => $categories[array_rand($categories)],
            'explanation' => 'Dummy explanation for category.',
            'confidence' => round(mt_rand(50, 100) / 100, 2),
        ];
    }
}
