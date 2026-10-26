<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'filename' => fake()->word().'.pdf',
            'content_type' => 'application/pdf',
            'size' => fake()->numberBetween(500, 50000),
            'path' => 'messages/'.fake()->uuid().'/attachments/0-'.fake()->lexify('????????'),
        ];
    }
}
