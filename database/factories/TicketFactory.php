<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Predefined English subjects for realistic help desk tickets
     */
    private static array $subjects = [
        'Email not working properly',
        'Cannot access my account',
        'Website is loading very slowly',
        'Payment was charged twice',
        'Password reset not working',
        'Application crashes on startup',
        'Need help with file upload',
        'Billing invoice shows wrong amount',
        'User permissions need updating',
        'Database connection timeout error',
        'Mobile app login issues',
        'Subscription renewal problems',
        'Report generation is failing',
        'API returning error messages',
        'Two-factor authentication broken',
        'Profile settings not saving',
        'Email notifications not received',
        'Data export feature broken',
        'Account locked after attempts',
        'Service downtime yesterday',
        'Feature request for dashboard',
        'Integration with third party',
        'Security concerns about data',
        'Training needed for new users',
        'Documentation is outdated'
    ];

    /**
     * Predefined English body content for realistic tickets
     */
    private static array $bodies = [
        'I am experiencing issues with this feature and need assistance. The problem started yesterday morning and has been persistent. Could you please help me resolve this matter?',
        'This has been a recurring problem for the past week. I have tried the usual troubleshooting steps but nothing seems to work. Please provide guidance on next steps.',
        'The system is not behaving as expected. I followed the documentation but encountered errors. Can someone from technical support please look into this issue?',
        'I need urgent help with this matter as it is affecting my daily work. The issue appears to be related to recent updates made to the system.',
        'Everything was working fine until recently. Now I am getting error messages and cannot complete my tasks. Please investigate and provide a solution.',
        'Could you please help me understand how to resolve this issue? I have checked the FAQ but could not find relevant information about this specific problem.',
        'This problem is preventing me from accessing important features. I have already tried restarting the application but the issue persists. Please assist.',
        'I am getting unexpected results when using this functionality. The behavior is different from what is described in the user manual. Need technical support.',
        'The feature worked correctly before but now it seems to have stopped functioning. I have not made any changes to my settings or configuration.',
        'Please help me resolve this technical issue. It started after the latest update and is causing significant disruption to my workflow and productivity.'
    ];

    /**
     * Available ticket categories
     */
    private static array $categories = ['billing', 'technical', 'general', 'account', 'other'];

    /**
     * Available ticket statuses
     */
    private static array $statuses = ['open', 'pending', 'closed'];

    /**
     * Sample notes for tickets
     */
    private static array $notes = [
        'Customer called for update',
        'Escalated to senior support',
        'Waiting for customer response',
        'Issue resolved via phone call',
        'Requires manager approval',
        'Follow up needed next week'
    ];

    /**
     * Sample explanations for AI classification
     */
    private static array $explanations = [
        'Categorized based on content analysis',
        'Issue related to user account settings',
        'Technical problem requiring investigation',
        'Billing inquiry needs finance team review',
        'General support request for assistance'
    ];

   public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'subject' => fake()->randomElement(self::$subjects),
            'body' => fake()->randomElement(self::$bodies),
            'status' => fake()->randomElement(self::$statuses),
            'category' => fake()->randomElement(self::$categories),
            'note' => fake()->optional(0.4)->randomElement(self::$notes),
            'explanation' => fake()->optional(0.7)->randomElement(self::$explanations),
            'confidence' => fake()->optional(0.8)->randomFloat(2, 0.5, 1),
        ];
    }
}
