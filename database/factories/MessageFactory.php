<?php

namespace Database\Factories;

use App\Models\Inbox;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inbox_id' => Inbox::factory(),
            'message_id' => '<'.fake()->uuid().'@example.com>',
            'from_address' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'to' => [['address' => fake()->safeEmail(), 'name' => null]],
            'cc' => [],
            'subject' => fake()->sentence(),
            'text_body' => fake()->paragraph(),
            'html_body' => '<p>'.fake()->paragraph().'</p>',
            'headers' => [['name' => 'X-Mailer', 'value' => 'Roostle']],
            'raw_path' => 'messages/'.fake()->uuid().'/message.eml',
            'size' => fake()->numberBetween(500, 50000),
            'received_at' => now(),
        ];
    }

    /**
     * Indicate that the message has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
