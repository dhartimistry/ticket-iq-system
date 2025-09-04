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

   public function definition(): array
    {
        $this->faker->locale = 'en_US'; // Force English locale on built-in faker
        $categories = ['billing', 'technical', 'general', 'account', 'other'];
        return [
            'id' => (string) Str::ulid(),
            'subject' => $this->faker->sentence(6, true),
            'body' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement(['open', 'pending', 'closed']),
            'category' => $this->faker->randomElement($categories),
            'note' => $this->faker->optional(0.4)->sentence(),
            'explanation' => $this->faker->optional(0.7)->sentence(),
            'confidence' => $this->faker->optional(0.8)->randomFloat(2, 0, 1),
        ];
    }
}
