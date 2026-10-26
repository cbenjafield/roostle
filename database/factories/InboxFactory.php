<?php

namespace Database\Factories;

use App\Models\Inbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Inbox>
 */
class InboxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'catch_token' => Str::lower(Str::random(16)),
            'smtp_username' => 'rst'.Str::lower(Str::random(16)),
            'smtp_password_hash' => Inbox::hashSmtpPassword('password'),
        ];
    }

    /**
     * Set the plain SMTP password for the inbox.
     */
    public function withSmtpPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'smtp_password_hash' => Inbox::hashSmtpPassword($password),
        ]);
    }
}
