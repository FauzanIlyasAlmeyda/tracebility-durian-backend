<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $suffix = fake()->unique()->randomNumber(6, true);
        $username = Str::lower($firstName.'.'.$lastName.'.'.$suffix);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => fake()->unique()->numerify('08##########'),
            'email' => $username.'@example.com',
            'username' => $username,
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'role' => fake()->randomElement(UserRole::values()),
            'is_active' => true,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
